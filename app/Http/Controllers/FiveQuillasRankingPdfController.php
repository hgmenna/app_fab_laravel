<?php

namespace App\Http\Controllers;

use App\Models\GeneralRanking;
use App\Models\RankingHistory;
use App\Models\Tournament;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Genera el documento PDF del ranking de cinco quillas.
 */
class FiveQuillasRankingPdfController extends Controller
{
    /**
     * Atiende la solicitud después de resolver autorización y datos publicados.
     */
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:10'],
            'federation' => ['nullable', 'string', 'max:255'],
            'club' => ['nullable', 'string', 'max:255'],
            'points_min' => ['nullable', 'numeric'],
            'points_max' => ['nullable', 'numeric'],
        ]);

        $latestRankingTournament = Tournament::query()
            ->whereHas('discipline', fn (Builder $query) => $query->where('code', 'five_quillas'))
            ->whereHas('type', fn (Builder $query) => $query->where('affects_ranking', true))
            ->whereIn('stage_number', [1, 2, 3, 4])
            ->where('end_date', '<=', now())
            ->orderByDesc('end_date')
            ->first();

        $currentSeason = $latestRankingTournament?->end_date?->year ?? now()->year;
        $previousSeason = $currentSeason - 1;

        $records = GeneralRanking::query()
            ->select('general_rankings.*')
            ->addSelect([
                'previous_rank' => RankingHistory::query()
                    ->select('RG')
                    ->whereColumn('player_id', 'general_rankings.player_id')
                    ->where('season', $previousSeason)
                    ->limit(1),
            ])
            ->when(
                filled($validated['category'] ?? null),
                fn (Builder $query) => $query->where('category', strtoupper(trim($validated['category'])))
            )
            ->when(
                filled($validated['federation'] ?? null),
                fn (Builder $query) => $query->where('fed', trim($validated['federation']))
            )
            ->when(
                filled($validated['club'] ?? null),
                fn (Builder $query) => $query->where('club', trim($validated['club']))
            )
            ->when(
                filled($validated['points_min'] ?? null),
                fn (Builder $query) => $query->where('total_puntos', '>=', $validated['points_min'])
            )
            ->when(
                filled($validated['points_max'] ?? null),
                fn (Builder $query) => $query->where('total_puntos', '<=', $validated['points_max'])
            )
            ->orderBy('RG')
            ->get();

        $pdf = Pdf::loadView('pdf.ranking', [
            'records' => $records,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('ranking-actual.pdf', [
            'Attachment' => false,
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
