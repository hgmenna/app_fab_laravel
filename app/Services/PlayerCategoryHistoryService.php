<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Player;
use App\Models\PlayerCategoryHistory;
use App\Models\Ranking5Quillas;
use App\Models\Tournament;
use Carbon\Carbon;
use Carbon\CarbonInterface;

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
     * Registrar cambio manual de categoría de afiliación.
     */
    public function recordManualCategoryChange(
        Player $player,
        Category $category,
        ?string $notes = null
    ): void {
        $previousCategory = $player->category;

        $this->createHistoryRecord(
            player: $player,
            category: $category,
            previousCategory: $previousCategory,
            source: 'manual',
            changeType: 'affiliation',
            season: now()->year,
            effectiveDate: now(),
            reason: 'Cambio manual de categoría',
            tournament: null,
            ranking: null,
            notes: $notes ?? 'Cambio manual de categoría'
        );
    }

    /**
     * Registrar cambio temporal producido por el Ranking General.
     *
     * Ejemplos:
     * P -> N
     * N -> M
     * M -> N
     * N -> P
     * S -> N
     */
    public function recordTemporaryRankingChange(
        Player $player,
        Category $previousCategory,
        Category $newCategory,
        int $season,
        CarbonInterface|string|null $effectiveDate = null,
        ?string $reason = null
    ): void {
        if ($previousCategory->id === $newCategory->id) {
            return;
        }

        $effectiveDate = $effectiveDate
            ? Carbon::parse($effectiveDate)
            : now();

        $lastHistory = PlayerCategoryHistory::query()
            ->where('player_id', $player->id)
            ->where('season', $season)
            ->where('change_type', 'ranking')
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
     *
     * Ejemplos:
     * S -> P
     * T -> S
     * PR -> T
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
        ?string $notes
    ): void {
        PlayerCategoryHistory::create([
            'player_id' => $player->id,
            'season' => $season,
            'category_id' => $category->id,
            'change_type' => $changeType,
            'previous_category_id' => $previousCategory?->id,
            'source' => $source,
            'tournament_id' => $tournament?->id,
            'ranking_id' => $ranking?->id,
            'effective_date' => $effectiveDate,
            'reason' => $reason,
            'notes' => $notes,
        ]);

    }

}