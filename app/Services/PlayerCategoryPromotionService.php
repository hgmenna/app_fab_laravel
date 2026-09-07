<?php

namespace App\Services;

use App\Models\Category;
use App\Models\PlayerCategoryHistory;
use App\Models\PlayerCategoryPromotion;
use App\Models\RankingHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlayerCategoryPromotionService
{
    /**
     * Determina los ascensos correspondientes al cierre de una temporada.
     *
     * Regla 1 - prioridad máxima:
     * Si un jugador cuya categoría de afiliación es S, T o PR termina
     * dentro de la zona temporal M/N, asciende directamente a Primera.
     *
     * Regla 2:
     * Si no fue alcanzado por la regla 1, el RC1 de cada categoría inferior
     * asciende un nivel:
     *
     * S  -> P
     * T  -> S
     * PR -> T
     *
     * El ascenso se determina al cierre de la temporada, pero se hace
     * efectivo el 1 de enero del año siguiente.
     */
    public static function determineSeasonPromotions(int $season): int
    {

        if (
            PlayerCategoryPromotion::query()
                ->where('season', $season)
                ->whereNotNull('applied_at')
                ->exists()
        ) {
            throw new \RuntimeException(
                "No se pueden recalcular las promociones de la temporada {$season}: existen ascensos que ya fueron aplicados."
            );
        }

        $promotionMap = config('ranking.promotion_map', [
            'S' => 'P',
            'T' => 'S',
            'PR' => 'T',
        ]);

        $temporaryRankingCategories = config(
            'ranking.temporary_ranking_categories',
            ['M', 'N']
        );

        $firstCategoryCode = config(
            'ranking.permanent_affiliation_category_for_temporary',
            'P'
        );

        $ranking = RankingHistory::query()
            ->where('season', $season)
            ->with('player.category')
            ->orderBy('RG')
            ->get();

        if ($ranking->isEmpty()) {
            throw new \RuntimeException(
                "No se pueden determinar ascensos: no existe RankingHistory para la temporada {$season}."
            );
        }

        /*
        * Cargamos las categorías por código para no depender de IDs fijos.
        */
        $requiredCodes = collect(array_keys($promotionMap))
            ->merge(array_values($promotionMap))
            ->push($firstCategoryCode)
            ->unique()
            ->values();

        $categories = Category::query()
            ->whereIn('code', $requiredCodes)
            ->get()
            ->keyBy('code');

        foreach ($requiredCodes as $code) {
            if (! $categories->has($code)) {
                throw new \RuntimeException(
                    "No existe la categoría con código {$code}."
                );
            }
        }

        $effectiveDate = Carbon::create(
            $season + 1,
            1,
            1
        )->startOfDay();

        $determinedPlayerIds = [];

        DB::transaction(function () use (
            $ranking,
            $season,
            $promotionMap,
            $temporaryRankingCategories,
            $firstCategoryCode,
            $categories,
            $effectiveDate,
            &$determinedPlayerIds
        ) {
            foreach ($ranking as $row) {
                $player = $row->player;

                if (! $player || ! $player->category) {
                    continue;
                }

                /*
                * Esta es la categoría permanente de afiliación del jugador.
                */
                $currentCategoryCode = $player->category->code;

                /*
                * Sólo las categorías inferiores participan de estas
                * reglas automáticas de ascenso.
                */
                if (! array_key_exists($currentCategoryCode, $promotionMap)) {
                    continue;
                }

                $newCategoryCode = null;
                $promotionType = null;
                $notes = null;

                /*
                * REGLA 1 - prioridad máxima.
                *
                * Si S/T/PR terminó como M o N en el Ranking General,
                * asciende directamente a Primera.
                */
                if (
                    in_array(
                        $row->category,
                        $temporaryRankingCategories,
                        true
                    )
                ) {
                    $newCategoryCode = $firstCategoryCode;
                    $promotionType = 'ranking_zone';
                    $notes = sprintf(
                        'Ascenso directo a %s por finalizar la temporada %d en zona %s del Ranking General.',
                        $firstCategoryCode,
                        $season,
                        $row->category
                    );
                }

                /*
                * REGLA 2.
                *
                * Si no fue alcanzado por la regla anterior y terminó RC1
                * de su categoría permanente, asciende un nivel.
                */
                elseif (
                    (int) $row->RC === 1
                    && $row->category === $currentCategoryCode
                ) {
                    $newCategoryCode = $promotionMap[$currentCategoryCode];
                    $promotionType = 'category_rc1';
                    $notes = sprintf(
                        'Ascenso de %s a %s por finalizar RC1 de la temporada %d.',
                        $currentCategoryCode,
                        $newCategoryCode,
                        $season
                    );
                }

                if (! $newCategoryCode) {
                    continue;
                }

                $newCategory = $categories->get($newCategoryCode);

                if (! $newCategory) {
                    throw new \RuntimeException(
                        "No se encontró la categoría destino {$newCategoryCode}."
                    );
                }

                PlayerCategoryPromotion::updateOrCreate(
                    [
                        'player_id' => $player->id,
                        'season' => $season,
                    ],
                    [
                        'previous_category_id' => $player->category->id,
                        'new_category_id' => $newCategory->id,
                        'promotion_type' => $promotionType,
                        'final_rg' => $row->RG,
                        'final_rc' => $row->RC,
                        'effective_date' => $effectiveDate,
                        'applied_at' => null,
                        'notes' => $notes,
                    ]
                );

                /*
                * El ascenso ya está determinado al cierre de la temporada.
                * Aunque todavía no modificamos players.category_id, dejamos
                * registrado desde ahora el cambio futuro en el historial.
                */
                $historyService = new PlayerCategoryHistoryService;

                $historyService->recordPendingSeasonPromotion(
                    player: $player,
                    previousCategory: $player->category,
                    newCategory: $newCategory,
                    season: $season,
                    effectiveDate: $effectiveDate,
                    reason: $notes,
                    notes: sprintf(
                        'Promoción determinada al cierre de la temporada %d. Aplicación efectiva: %s.',
                        $season,
                        $effectiveDate->format('d/m/Y')
                    )
                );

                $determinedPlayerIds[] = $player->id;
            }

            /*
            * Si se vuelve a determinar la misma temporada después de una
            * corrección del ranking, eliminamos promociones pendientes que
            * ya no correspondan.
            *
            * Nunca eliminamos promociones que ya hayan sido aplicadas.
            */
            $stalePromotions = PlayerCategoryPromotion::query()
                ->where('season', $season)
                ->whereNull('applied_at');

            if (! empty($determinedPlayerIds)) {
                $stalePromotions->whereNotIn(
                    'player_id',
                    $determinedPlayerIds
                );
            }

            /*
            * Antes de eliminar promociones que dejaron de corresponder,
            * eliminamos únicamente su historial pendiente asociado.
            *
            * Nunca tocamos historiales ya aplicados.
            */
            $stalePlayerIds = (clone $stalePromotions)
                ->pluck('player_id');

            if ($stalePlayerIds->isNotEmpty()) {
                PlayerCategoryHistory::query()
                    ->whereIn('player_id', $stalePlayerIds)
                    ->where('season', $season)
                    ->where('source', 'season_promotion')
                    ->where('change_type', 'affiliation')
                    ->whereNull('applied_at')
                    ->delete();
            }

            $stalePromotions->delete();
        });

        return count(array_unique($determinedPlayerIds));
    }

    /**
     * Sincroniza en el historial de categorías las promociones pendientes
     * que ya fueron determinadas para una temporada.
     *
     * Este método NO modifica la categoría actual del jugador.
     * Solamente garantiza que cada promoción pendiente tenga su
     * correspondiente registro de historial también pendiente.
     *
     * Es útil, entre otros casos, para promociones determinadas antes
     * de incorporar el registro inmediato del historial.
     */
    public static function syncPendingPromotionHistories(int $season): int
    {
        $promotions = PlayerCategoryPromotion::query()
            ->with([
                'player.category',
                'previousCategory',
                'newCategory',
            ])
            ->where('season', $season)
            ->whereNull('applied_at')
            ->orderBy('id')
            ->get();

        if ($promotions->isEmpty()) {
            return 0;
        }

        $historyService = new PlayerCategoryHistoryService;
        $synced = 0;

        DB::transaction(function () use (
            $promotions,
            $historyService,
            &$synced
        ) {
            foreach ($promotions as $promotion) {
                $player = $promotion->player;
                $previousCategory = $promotion->previousCategory;
                $newCategory = $promotion->newCategory;

                if (! $player || ! $previousCategory || ! $newCategory) {
                    throw new \RuntimeException(
                        "La promoción ID {$promotion->id} tiene relaciones incompletas."
                    );
                }

                /*
                * La promoción todavía no fue aplicada, por lo que la categoría
                * permanente del jugador debe seguir coincidiendo con la categoría
                * de origen con la cual fue determinada.
                */
                if ((int) $player->category_id !== (int) $previousCategory->id) {
                    throw new \RuntimeException(
                        "No se puede sincronizar la promoción ID {$promotion->id} del jugador {$player->id}: "
                        .'su categoría actual ya no coincide con la categoría de origen.'
                    );
                }

                $historyService->recordPendingSeasonPromotion(
                    player: $player,
                    previousCategory: $previousCategory,
                    newCategory: $newCategory,
                    season: (int) $promotion->season,
                    effectiveDate: $promotion->effective_date,
                    reason: $promotion->notes,
                    notes: sprintf(
                        'Promoción determinada al cierre de la temporada %d. Aplicación efectiva: %s.',
                        $promotion->season,
                        $promotion->effective_date->format('d/m/Y')
                    )
                );

                $synced++;
            }
        });

        return $synced;
    }

    /**
     * Aplica las promociones cuya fecha efectiva ya llegó.
     *
     * Actualiza la categoría permanente del jugador y registra
     * el cambio en el historial de categorías.
     */
    public static function applyDuePromotions(): int
    {
        $promotions = PlayerCategoryPromotion::query()
            ->with([
                'player.category',
                'previousCategory',
                'newCategory',
            ])
            ->whereNull('applied_at')
            ->whereDate('effective_date', '<=', today())
            ->orderBy('effective_date')
            ->orderBy('id')
            ->get();

        if ($promotions->isEmpty()) {
            return 0;
        }

        $applied = 0;

        foreach ($promotions as $promotion) {
            try {
                DB::transaction(function () use ($promotion, &$applied) {
                    $historyService = new PlayerCategoryHistoryService;

                    $player = $promotion->player;
                    $previousCategory = $promotion->previousCategory;
                    $newCategory = $promotion->newCategory;

                    if (! $player || ! $previousCategory || ! $newCategory) {
                        throw new \RuntimeException(
                            "La promoción ID {$promotion->id} tiene relaciones incompletas."
                        );
                    }

                    /*
                    * Protección:
                    * la categoría actual debe seguir siendo la misma categoría
                    * desde la cual se determinó el ascenso.
                    *
                    * Si fue modificada manualmente entre el cierre de temporada
                    * y la fecha efectiva, no sobrescribimos ese cambio.
                    */
                    if ((int) $player->category_id !== (int) $previousCategory->id) {
                        throw new \RuntimeException(
                            "No se puede aplicar la promoción ID {$promotion->id} del jugador {$player->id}: "
                            .'su categoría actual ya no coincide con la categoría de origen.'
                        );
                    }

                    /*
                    * La promoción ya fue registrada como pendiente cuando se
                    * determinó al cierre de la temporada.
                    *
                    * Primero aplicamos realmente la nueva categoría al jugador.
                    */
                    $player->category_id = $newCategory->id;
                    $player->save();

                    /*
                    * Marcamos como aplicado el mismo registro de historial.
                    * No creamos una segunda fila.
                    */
                    $historyService->markSeasonPromotionHistoryApplied(
                        player: $player,
                        previousCategory: $previousCategory,
                        newCategory: $newCategory,
                        season: (int) $promotion->season,
                        effectiveDate: $promotion->effective_date,
                        reason: $promotion->notes
                    );

                    $promotion->applied_at = now();
                    $promotion->save();

                    $applied++;
                });
            } catch (\Throwable $e) {
                Log::warning(
                    'No se pudo aplicar una promoción de categoría.',
                    [
                        'promotion_id' => $promotion->id,
                        'player_id' => $promotion->player_id,
                        'season' => $promotion->season,
                        'error' => $e->getMessage(),
                    ]
                );

                continue;
            }
        }

        return $applied;
    }
}
