<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Category;
use App\Models\Tournament;
use App\Models\Ranking5Quillas;
use App\Models\PlayerCategoryHistory;

class PlayerCategoryHistoryService
{
    /**
     * Registrar categoría por afiliación inicial.
     */
    public function recordAffiliationCategory(Player $player, Category $category, ?string $notes = null): void
    {
        $this->createHistoryRecord(
            player: $player,
            category: $category,
            source: 'affiliation',
            tournament: null,
            ranking: null,
            notes: $notes
        );
    }

    /**
     * Registrar categoría del jugador en un torneo.
     */
    public function recordTournamentCategory(Player $player, Category $category, Tournament $tournament): void
    {
        $this->createHistoryRecord(
            player: $player,
            category: $category,
            source: 'tournament',
            tournament: $tournament,
            ranking: null,
            notes: "Categoría del jugador en el torneo {$tournament->name}"
        );
    }

    /**
     * Registrar categoría asignada por ranking (M o N).
     */
    public function recordRankingCategory(Player $player, Category $category, Ranking5Quillas $ranking): void
    {
        $this->createHistoryRecord(
            player: $player,
            category: $category,
            source: 'ranking',
            tournament: null,
            ranking: $ranking,
            notes: "Categoría asignada por ranking nacional"
        );
    }

    /**
     * Registrar cambio manual de categoría.
     */
    public function recordManualCategoryChange(Player $player, Category $category, ?string $notes = null): void
    {
        $this->createHistoryRecord(
            player: $player,
            category: $category,
            source: 'manual',
            tournament: null,
            ranking: null,
            notes: $notes ?? 'Cambio manual de categoría'
        );
    }

    /**
     * Método centralizado para crear registros de historial.
     */
    private function createHistoryRecord(
        Player $player,
        Category $category,
        string $source,
        ?Tournament $tournament,
        ?Ranking5Quillas $ranking,
        ?string $notes
    ): void {
        PlayerCategoryHistory::create([
            'player_id' => $player->id,
            'category_id' => $category->id,
            'source' => $source,
            'tournament_id' => $tournament?->id,
            'ranking_id' => $ranking?->id,
            'notes' => $notes,
        ]);
    }
}
