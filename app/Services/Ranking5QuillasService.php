<?php

namespace App\Services;

use App\Models\Player;
use App\Models\Category;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\Ranking5Quillas;

class Ranking5QuillasService
{
    protected int $disciplineId;

    public function recalculate(): void
    {
        $tournaments = Tournament::whereHas('type', fn($q) =>
            $q->where('affects_ranking', true)
        )
        ->where('discipline_id', $this->disciplineId)
        ->orderBy('start_date', 'desc')
        ->take(4)
        ->get();

        $players = Player::all();

        foreach ($players as $player) {
            $this->updatePlayerRanking($player, $tournaments);
        }

        $this->assignPositions();
    }

    private function updatePlayerRanking(Player $player, $tournaments): void
    {
        $regs = TournamentRegistration::where('player_id', $player->id)
            ->whereIn('tournament_id', $tournaments->pluck('id'))
            ->get()
            ->keyBy('tournament_id');

        Ranking5Quillas::updateOrCreate(
            ['player_id' => $player->id],
            [
                'category_id' => $player->category_id,
                'club_id' => $player->club_id,
                'total_points' => $regs->sum('points_awarded'),

                'stage_1_points' => $regs[$tournaments[0]->id]->points_awarded ?? 0,
                'stage_1_position' => $regs[$tournaments[0]->id]->instance->instance ?? null,

                'stage_2_points' => $regs[$tournaments[1]->id]->points_awarded ?? 0,
                'stage_2_position' => $regs[$tournaments[1]->id]->instance->instance ?? null,

                'stage_3_points' => $regs[$tournaments[2]->id]->points_awarded ?? 0,
                'stage_3_position' => $regs[$tournaments[2]->id]->instance->instance ?? null,

                'stage_4_points' => $regs[$tournaments[3]->id]->points_awarded ?? 0,
                'stage_4_position' => $regs[$tournaments[3]->id]->instance->instance ?? null,
            ]
        );
    }

    private function assignPositions(): void
    {
        $rankings = Ranking5Quillas::orderBy('total_points', 'desc')->get();

        $pos = 1;
        foreach ($rankings as $r) {
            $r->rg_position = $pos++;
            $r->save();
        }

        $categories = Category::all();
        foreach ($categories as $cat) {
            $catRankings = Ranking5Quillas::where('category_id', $cat->id)
                ->orderBy('total_points', 'desc')
                ->get();

            $pos = 1;
            foreach ($catRankings as $r) {
                $r->rc_position = $pos++;
                $r->save();
            }
        }
    }
}
