<?php

namespace App\Http\Controllers;

use App\Models\ThreeCushionRanking;
use App\Services\ThreeCushionRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThreeCushionRankingController extends Controller
{
    public function __invoke(Request $request, ThreeCushionRankingService $service): JsonResponse
    {
        $season = max(2000, (int) $request->integer('year', now()->year));
        $discipline = $service->discipline();

        if ($discipline) {
            $service->syncSeason($season, $discipline->id);
        }

        $query = ThreeCushionRanking::query()
            ->with(['player.club', 'category'])
            ->where('season', $season)
            ->orderBy('category_id')
            ->orderBy('position');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        } elseif ($request->filled('category')) {
            $query->whereHas('category', fn ($categoryQuery) => $categoryQuery
                ->where('code', $request->string('category')->toString()));
        }

        $rows = $query->get()->map(fn (ThreeCushionRanking $row): array => [
            'position' => $row->position,
            'player_id' => $row->player_id,
            'player' => $row->player?->full_name,
            'club' => $row->player?->club?->name,
            'category_id' => $row->category_id,
            'category' => $row->category?->name,
            'category_code' => $row->category?->code,
            'total_caroms' => $row->total_caroms,
            'total_innings' => $row->total_innings,
            'high_run' => $row->high_run,
            'general_average' => round((float) $row->general_average, 3),
            'best_match_average' => round((float) $row->best_match_average, 3),
            'ranking_points' => (float) $row->ranking_points,
            'stages_played' => $row->stages_played,
            'total_stages' => $row->total_stages,
            'participations' => "{$row->stages_played} / {$row->total_stages}",
            'stages' => $row->stageDetails()->map(fn ($result): array => [
                'tournament' => $result->registration?->tournament?->name,
                'date' => $result->registration?->tournament?->end_date?->format('Y-m-d'),
                'position' => $result->registration?->result_description
                    ?? $result->registration?->tournamentInstance?->description,
                'caroms' => $result->caroms,
                'innings' => $result->innings,
                'general_average' => round((float) $result->general_average, 3),
                'best_match_average' => round((float) $result->best_match_average, 3),
                'high_run' => $result->high_run,
                'ranking_points' => (float) $result->registration?->points,
            ])->all(),
        ]);

        return response()->json([
            'discipline' => $discipline?->name ?? 'Carambola 3 Bandas',
            'season' => $season,
            'generated_at' => now()->toIso8601String(),
            'data' => $rows,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
