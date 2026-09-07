<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Player;
use App\Models\PlayerCategoryHistory;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlayerCategoryChangeService
{
    /**
     * Programa o aplica un cambio manual de categoría permanente.
     *
     * Si la fecha efectiva ya llegó, se aplica inmediatamente.
     * Si es futura, queda pendiente hasta esa fecha.
     */
    public static function scheduleManualChange(
        Player $player,
        Category $newCategory,
        CarbonInterface|string $effectiveDate,
        string $reason,
        ?string $notes = null
    ): PlayerCategoryHistory {
        $effectiveDate = Carbon::parse($effectiveDate)->startOfDay();
        $reason = trim($reason);

        if ($reason === '') {
            throw new \RuntimeException(
                'Debe indicarse el motivo del cambio de categoría.'
            );
        }

        $player->loadMissing('category');

        $previousCategory = $player->category;

        if (! $previousCategory) {
            throw new \RuntimeException(
                'El jugador no tiene una categoría actual asignada.'
            );
        }

        if ((int) $previousCategory->id === (int) $newCategory->id) {
            throw new \RuntimeException(
                'La nueva categoría debe ser diferente de la categoría actual.'
            );
        }

        /*
         * Master y Nacional son categorías temporales del Ranking General.
         * No pueden asignarse como categoría permanente de afiliación.
         */
        $temporaryCategoryCodes = config(
            'ranking.temporary_ranking_categories',
            ['M', 'N']
        );

        if (in_array($newCategory->code, $temporaryCategoryCodes, true)) {
            throw new \RuntimeException(
                "La categoría {$newCategory->code} es temporal del Ranking General "
                . 'y no puede asignarse como categoría permanente.'
            );
        }

        /*
         * Evitamos tener dos cambios manuales pendientes para el mismo jugador.
         * Esto impide cadenas ambiguas de categorías de origen.
         */
        $hasPendingChange = PlayerCategoryHistory::query()
            ->where('player_id', $player->id)
            ->where('source', 'manual')
            ->whereNull('applied_at')
            ->exists();

        if ($hasPendingChange) {
            throw new \RuntimeException(
                'El jugador ya tiene un cambio manual de categoría pendiente.'
            );
        }

        return DB::transaction(function () use (
            $player,
            $previousCategory,
            $newCategory,
            $effectiveDate,
            $reason,
            $notes
        ) {
            $historyService = new PlayerCategoryHistoryService();

            /*
             * Siempre se crea inicialmente como pendiente.
             * Si la fecha ya llegó, se aplica inmediatamente a continuación.
             */
            $history = $historyService->recordManualCategoryChange(
                player: $player,
                previousCategory: $previousCategory,
                newCategory: $newCategory,
                effectiveDate: $effectiveDate,
                reason: $reason,
                notes: $notes,
                applied: false
            );

            if ($effectiveDate->lte(today())) {
                static::applyChange($history);
            }

            return $history->refresh();
        });
    }

    /**
     * Aplica un cambio manual pendiente.
     */
    public static function applyChange(
        PlayerCategoryHistory $history
    ): void {
        if ($history->applied_at) {
            return;
        }

        if (
            $history->source !== 'manual'
            || $history->change_type !== 'affiliation'
        ) {
            throw new \RuntimeException(
                "El historial ID {$history->id} no corresponde "
                . 'a un cambio manual de afiliación.'
            );
        }

        if (
            ! $history->effective_date
            || $history->effective_date->isFuture()
        ) {
            throw new \RuntimeException(
                "El cambio ID {$history->id} todavía no llegó "
                . 'a su fecha efectiva.'
            );
        }

        $history->loadMissing([
            'player.category',
            'previousCategory',
            'category',
        ]);

        $player = $history->player;
        $previousCategory = $history->previousCategory;
        $newCategory = $history->category;

        if (! $player || ! $previousCategory || ! $newCategory) {
            throw new \RuntimeException(
                "El historial ID {$history->id} tiene relaciones incompletas."
            );
        }

        /*
         * Protección contra sobrescrituras:
         * la categoría actual debe continuar siendo aquella desde la cual
         * se programó el cambio.
         */
        if ((int) $player->category_id !== (int) $previousCategory->id) {
            throw new \RuntimeException(
                "No se puede aplicar el cambio ID {$history->id} "
                . "del jugador {$player->id}: "
                . 'su categoría actual ya no coincide con la categoría de origen.'
            );
        }

        $player->category_id = $newCategory->id;
        $player->save();

        $history->applied_at = now();
        $history->save();
    }

    /**
     * Aplica todos los cambios manuales cuya fecha efectiva ya llegó.
     *
     * Cada cambio se procesa en su propia transacción para que el error
     * de un jugador no impida aplicar los restantes.
     */
    public static function applyDueChanges(): int
    {
        $changes = PlayerCategoryHistory::query()
            ->where('source', 'manual')
            ->where('change_type', 'affiliation')
            ->whereNull('applied_at')
            ->whereDate('effective_date', '<=', today())
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();

        if ($changes->isEmpty()) {
            return 0;
        }

        $applied = 0;

        foreach ($changes as $history) {
            try {
                DB::transaction(function () use ($history, &$applied) {
                    static::applyChange($history);

                    $applied++;
                });
            } catch (\Throwable $e) {
                Log::warning(
                    'No se pudo aplicar un cambio manual de categoría.',
                    [
                        'history_id' => $history->id,
                        'player_id' => $history->player_id,
                        'effective_date' => $history->effective_date?->format('Y-m-d'),
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        return $applied;
    }
}
