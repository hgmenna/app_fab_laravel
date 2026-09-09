<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Player;
use App\Models\PlayerCategoryHistory;
use App\Models\Ranking5Quillas;
use App\Models\Tournament;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PlayerCategoryHistoryService
{
    /**
     * Registrar categoría por afiliación inicial.
     */
    public function recordAffiliationCategory(
        Player $player,
        Category $category,
        ?string $notes = null
    ): void {
        $this->createHistoryRecord(
            player: $player,
            category: $category,
            previousCategory: null,
            source: 'affiliation',
            changeType: 'affiliation',
            season: null,
            effectiveDate: now(),
            reason: 'Afiliación inicial',
            tournament: null,
            ranking: null,
            notes: $notes
        );
    }

    /**
     * Registrar categoría del jugador en un torneo.
     */
    public function recordTournamentCategory(
        Player $player,
        Category $category,
        Tournament $tournament
    ): void {
        $this->createHistoryRecord(
            player: $player,
            category: $category,
            previousCategory: null,
            source: 'tournament',
            changeType: 'tournament',
            season: $tournament->end_date?->year,
            effectiveDate: $tournament->end_date ?? now(),
            reason: 'Categoría utilizada en torneo',
            tournament: $tournament,
            ranking: null,
            notes: "Categoría del jugador en el torneo {$tournament->name}"
        );
    }

    /**
     * Registrar categoría asignada por ranking antiguo.
     */
    public function recordRankingCategory(
        Player $player,
        Category $category,
        Ranking5Quillas $ranking
    ): void {
        $this->createHistoryRecord(
            player: $player,
            category: $category,
            previousCategory: null,
            source: 'ranking',
            changeType: 'ranking',
            season: now()->year,
            effectiveDate: now(),
            reason: 'Categoría asignada por ranking nacional',
            tournament: null,
            ranking: $ranking,
            notes: 'Categoría asignada por ranking nacional'
        );
    }

    /**
     * Crear un cambio manual de categoría.
     *
     * Puede quedar pendiente si la fecha efectiva es futura.
     */
    public function recordManualCategoryChange(
        Player $player,
        Category $previousCategory,
        Category $newCategory,
        CarbonInterface|string $effectiveDate,
        string $reason,
        ?string $notes = null,
        bool $applied = false
    ): PlayerCategoryHistory {
        $effectiveDate = Carbon::parse($effectiveDate);

        return $this->createHistoryRecord(
            player: $player,
            category: $newCategory,
            previousCategory: $previousCategory,
            source: 'manual',
            changeType: 'affiliation',
            season: $effectiveDate->year,
            effectiveDate: $effectiveDate,
            reason: $reason,
            tournament: null,
            ranking: null,
            notes: $notes,
            applied: $applied
        );
    }

    /**
     * Registrar cambio temporal producido por el Ranking General.
     */
    public function recordTemporaryRankingChange(
        Player $player,
        Category $previousCategory,
        Category $newCategory,
        int $season,
        CarbonInterface|string|null $effectiveDate = null,
        ?string $reason = null
    ): void {
        if ((int) $previousCategory->id === (int) $newCategory->id) {
            return;
        }

        $effectiveDate = $effectiveDate
            ? Carbon::parse($effectiveDate)
            : now();

        $duplicateExists = PlayerCategoryHistory::query()
            ->where('player_id', $player->id)
            ->where('season', $season)
            ->where('change_type', 'ranking')
            ->where('previous_category_id', $previousCategory->id)
            ->where('category_id', $newCategory->id)
            ->whereDate('effective_date', $effectiveDate->toDateString())
            ->exists();

        if ($duplicateExists) {
            return;
        }

        $this->createHistoryRecord(
            player: $player,
            category: $newCategory,
            previousCategory: $previousCategory,
            source: 'ranking',
            changeType: 'ranking',
            season: $season,
            effectiveDate: $effectiveDate,
            reason: $reason ?? 'Cambio temporal por Ranking General',
            tournament: null,
            ranking: null,
            notes: null
        );
    }

    /**
     * Registrar cambio permanente de categoría de afiliación.
     */
    public function recordAffiliationChange(
        Player $player,
        Category $previousCategory,
        Category $newCategory,
        int $season,
        CarbonInterface|string $effectiveDate,
        ?string $reason = null
    ): void {
        if ($previousCategory->id === $newCategory->id) {
            return;
        }

        $effectiveDate = Carbon::parse($effectiveDate);

        $lastHistory = PlayerCategoryHistory::query()
            ->where('player_id', $player->id)
            ->where('season', $season)
            ->where('change_type', 'affiliation')
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        if ($lastHistory?->category_id === $newCategory->id) {
            return;
        }

        $this->createHistoryRecord(
            player: $player,
            category: $newCategory,
            previousCategory: $previousCategory,
            source: 'season_promotion',
            changeType: 'affiliation',
            season: $season,
            effectiveDate: $effectiveDate,
            reason: $reason ?? 'Ascenso de categoría al cierre de temporada',
            tournament: null,
            ranking: null,
            notes: null
        );
    }

    /**
     * Registrar o actualizar un ascenso de temporada todavía pendiente.
     *
     * Se crea cuando se determinan las promociones al cierre de la Etapa 4,
     * aunque la categoría permanente recién cambie en la fecha efectiva.
     */
    public function recordPendingSeasonPromotion(
        Player $player,
        Category $previousCategory,
        Category $newCategory,
        int $season,
        CarbonInterface|string $effectiveDate,
        ?string $reason = null,
        ?string $notes = null
    ): PlayerCategoryHistory {
        if ($previousCategory->id === $newCategory->id) {
            throw new \RuntimeException(
                "La categoría anterior y la nueva son iguales para el jugador {$player->id}."
            );
        }

        $effectiveDate = Carbon::parse($effectiveDate);

        $existingHistory = PlayerCategoryHistory::query()
            ->where('player_id', $player->id)
            ->where('season', $season)
            ->where('source', 'season_promotion')
            ->where('change_type', 'affiliation')
            ->first();

        if ($existingHistory?->applied_at) {
            throw new \RuntimeException(
                "No se puede volver a dejar pendiente el ascenso de temporada del jugador {$player->id}: el historial ya fue aplicado."
            );
        }

        return PlayerCategoryHistory::updateOrCreate(
            [
                'player_id' => $player->id,
                'season' => $season,
                'source' => 'season_promotion',
                'change_type' => 'affiliation',
            ],
            [
                'previous_category_id' => $previousCategory->id,
                'category_id' => $newCategory->id,
                'tournament_id' => null,
                'ranking_id' => null,
                'effective_date' => $effectiveDate,
                'applied_at' => null,
                'reason' => $reason ?? 'Ascenso de categoría al cierre de temporada',
                'notes' => $notes,
            ]
        );
    }

    /**
     * Marcar como aplicado el mismo historial creado cuando se determinó
     * el ascenso de temporada.
     */
    public function markSeasonPromotionHistoryApplied(
        Player $player,
        Category $previousCategory,
        Category $newCategory,
        int $season,
        CarbonInterface|string $effectiveDate,
        ?string $reason = null
    ): PlayerCategoryHistory {
        $effectiveDate = Carbon::parse($effectiveDate);

        $history = PlayerCategoryHistory::query()
            ->where('player_id', $player->id)
            ->where('season', $season)
            ->where('source', 'season_promotion')
            ->where('change_type', 'affiliation')
            ->where('previous_category_id', $previousCategory->id)
            ->where('category_id', $newCategory->id)
            ->first();

        /*
        * Protección para datos históricos anteriores a esta funcionalidad:
        * si por alguna razón no existe el pendiente, lo creamos.
        */
        if (! $history) {
            $history = $this->recordPendingSeasonPromotion(
                player: $player,
                previousCategory: $previousCategory,
                newCategory: $newCategory,
                season: $season,
                effectiveDate: $effectiveDate,
                reason: $reason
            );
        }

        $history->effective_date = $effectiveDate;
        $history->reason = $reason ?? $history->reason;
        $history->applied_at = now();
        $history->save();

        return $history;
    }

    /**
     * Obtener la categoría efectiva vigente del jugador.
     *
     * Considera solamente movimientos que ya fueron aplicados.
     * Puede devolver una categoría temporal de ranking (M/N)
     * o una categoría permanente.
     *
     * Si el jugador todavía no tiene un historial aplicable,
     * utiliza su categoría actual de afiliación.
    */
    public function getEffectiveCategory(Player $player): ?Category
    {
        $lastHistory = PlayerCategoryHistory::query()
            ->where('player_id', $player->id)
            ->whereNotNull('applied_at')
            ->whereIn('source', [
                'affiliation',
                'ranking',
                'manual',
                'season_promotion',
            ])
            ->with('category')
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->first();

        return $lastHistory?->category ?? $player->category;
    }

    /**
     * Obtener la categoría permanente vigente del jugador.
     *
     * Master (M) y Nacional (N) son categorías temporales del
     * Ranking General y no modifican players.category_id.
     *
     * Por lo tanto, la categoría permanente vigente es siempre
     * la categoría de afiliación actualmente almacenada en Player.
     */
    public function getPermanentCategory(Player $player): ?Category
    {
        return $player->category;
    }

    /**
     * Obtener los jugadores cuya última categoría efectiva vigente
     * es una categoría temporal del Ranking General (M o N).
     *
     * Durante la carga parcial de una etapa el GeneralRanking puede
     * cambiar, pero el historial permanece intacto hasta que todos
     * los resultados estén completos.
     *
     * Por eso este conjunto representa el estado oficial anterior
     * que debemos conservar para detectar correctamente salidas de
     * M/N al finalizar la carga de una nueva etapa.
     */
    public function getPlayersWithEffectiveTemporaryRankingCategory(): Collection
    {
        $latestHistories = PlayerCategoryHistory::query()
            ->whereNotNull('applied_at')
            ->whereIn('source', [
                'affiliation',
                'ranking',
                'manual',
                'season_promotion',
            ])
            ->with('category')
            ->orderBy('player_id')
            ->orderByDesc('effective_date')
            ->orderByDesc('id')
            ->get()
            ->unique('player_id');

        return $latestHistories
            ->filter(
                fn (PlayerCategoryHistory $history): bool =>
                    in_array(
                        $history->category?->code,
                        ['M', 'N'],
                        true
                    )
            )
            ->pluck('player_id')
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Método centralizado para crear registros de historial.
    */
    private function createHistoryRecord(
        Player $player,
        Category $category,
        ?Category $previousCategory,
        string $source,
        string $changeType,
        ?int $season,
        CarbonInterface|string|null $effectiveDate,
        ?string $reason,
        ?Tournament $tournament,
        ?Ranking5Quillas $ranking,
        ?string $notes,
        bool $applied = true
    ): PlayerCategoryHistory {
        return PlayerCategoryHistory::create([
            'player_id' => $player->id,
            'season' => $season,
            'category_id' => $category->id,
            'change_type' => $changeType,
            'previous_category_id' => $previousCategory?->id,
            'source' => $source,
            'tournament_id' => $tournament?->id,
            'ranking_id' => $ranking?->id,
            'effective_date' => $effectiveDate,
            'applied_at' => $applied ? now() : null,
            'reason' => $reason,
            'notes' => $notes,
        ]);
    }
}
