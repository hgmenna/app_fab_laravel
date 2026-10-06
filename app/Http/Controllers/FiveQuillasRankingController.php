<?php

namespace App\Http\Controllers;

use App\Models\RankingHistory;
use App\Models\Tournament;
use App\Services\RankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FiveQuillasRankingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $category = $request->filled('category')
            ? strtoupper(trim($request->string('category')->toString()))
            : null;
        $search = $request->filled('search')
            ? trim($request->string('search')->toString())
            : null;

        // Se calcula desde las inscripciones y puntuaciones actuales para que
        // WordPress no dependa de una copia que pueda haber quedado desactualizada.
        $ranking = RankingService::getGeneralRanking($category, $search);

        $latestTournament = Tournament::query()
            ->whereHas('discipline', fn ($query) => $query->where('code', 'five_quillas'))
            ->whereHas('type', fn ($query) => $query->where('affects_ranking', true))
            ->whereIn('stage_number', [1, 2, 3, 4])
            ->whereDate('end_date', '<=', today())
            ->orderByDesc('end_date')
            ->first();

        $season = $latestTournament?->end_date?->year ?? now()->year;
        $previousRanks = RankingHistory::query()
            ->where('season', $season - 1)
            ->pluck('RG', 'player_id');

        $rows = $ranking->map(fn (array $row): array => [
            'ranking_general' => (int) $row['RG'],
            'ranking_categoria' => (int) $row['RC'],
            'ranking_anterior' => $previousRanks->has($row['player_id'])
                ? (int) $previousRanks->get($row['player_id'])
                : null,
            'player_id' => (int) $row['player_id'],
            'apellido' => $row['last_name'],
            'nombre' => $row['first_name'],
            'jugador' => trim(($row['last_name'] ?? '').', '.($row['first_name'] ?? ''), ', '),
            'club' => $row['club'],
            'federacion' => $row['fed'],
            'categoria' => $row['category'],
            'penalizaciones' => (float) $row['total_penalties'],
            'puntos_total' => (float) $row['total_puntos'],
            'etapa_1_posicion' => $row['pos_1'],
            'etapa_1_puntos' => $row['ptos_1'] === null ? null : (float) $row['ptos_1'],
            'etapa_2_posicion' => $row['pos_2'],
            'etapa_2_puntos' => $row['ptos_2'] === null ? null : (float) $row['ptos_2'],
            'etapa_3_posicion' => $row['pos_3'],
            'etapa_3_puntos' => $row['ptos_3'] === null ? null : (float) $row['ptos_3'],
            'etapa_4_posicion' => $row['pos_4'],
            'etapa_4_puntos' => $row['ptos_4'] === null ? null : (float) $row['ptos_4'],
        ])->values();

        return response()->json([
            'discipline' => '5 Quillas',
            'ranking' => 'Circuito Argentino de 5 Quillas',
            'season' => $season,
            'generated_at' => now()->toIso8601String(),
            'filters' => [
                'category' => $category,
                'search' => $search,
            ],
            'data' => $rows,
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Access-Control-Allow-Origin' => '*',
        ]);
    }
}
