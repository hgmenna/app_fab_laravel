<?php

namespace App\Http\Controllers;

use App\Models\FiveQuillasCompetition;
use Illuminate\Http\JsonResponse;

class FiveQuillasCompetitionPublicController extends Controller
{
    public function __invoke(string $slug): JsonResponse
    {
        $competition = FiveQuillasCompetition::query()
            ->where('public_slug', $slug)
            ->where('is_public', true)
            ->with([
                'tournament:id,name,start_date,end_date,flyer_path',
                'phases' => fn ($query) => $query->where('is_public', true)->where('is_enabled', true),
                'phases.groups' => fn ($query) => $query->where('is_public', true),
                'phases.groups.slot:id,name,starts_at',
                'phases.groups.groupEntries.entry.player:id,first_name,last_name,club_id',
                'phases.groups.groupEntries.entry.player.club:id,name,short_name',
                'phases.matches' => fn ($query) => $query->where('is_public', true),
                'phases.matches.playerOne.player:id,first_name,last_name',
                'phases.matches.playerTwo.player:id,first_name,last_name',
                'phases.matches.winner.player:id,first_name,last_name',
            ])->firstOrFail();

        return response()->json([
            'data' => [
                'slug' => $competition->public_slug,
                'status' => $competition->status,
                'torneo' => $competition->tournament->name,
                'fecha_inicio' => $competition->tournament->start_date?->toDateString(),
                'fecha_fin' => $competition->tournament->end_date?->toDateString(),
                'fases' => $competition->phases->map(fn ($phase): array => [
                    'codigo' => $phase->code,
                    'nombre' => $phase->name,
                    'tipo' => $phase->phase_type,
                    'estado' => $phase->status,
                    'sets' => $phase->sets_to_play,
                    'puntos_por_set' => $phase->points_per_set,
                    'dividida_por_horario' => $phase->split_by_slot,
                    'clasificados_siguiente_fase' => data_get($phase->settings, 'qualifiers'),
                    'mesas' => data_get($phase->settings, 'tables'),
                    'tamano_cuadro' => data_get($phase->settings, 'bracket_size'),
                    'orden_cruces' => data_get($phase->settings, 'bracket_pairs', []),
                    'horarios_fase' => collect(data_get($phase->settings, 'schedules', []))->values(),
                    'zonas' => $phase->groups->map(fn ($group): array => [
                        'id' => $group->id, 'nombre' => $group->name, 'estado' => $group->status,
                        'horario' => $group->slot?->name,
                        'inicio' => $group->slot?->starts_at?->toIso8601String(),
                        'jugadores' => $group->groupEntries->map(fn ($row): array => [
                            'posicion' => $row->position,
                            'nombre' => $row->entry->player->full_name,
                            'club' => $row->entry->player->club?->short_name ?: $row->entry->player->club?->name,
                            'partidos_ganados' => $row->match_wins,
                            'diferencia_sets' => $row->sets_difference,
                            'puntos_favor' => $row->points_for,
                            'puntos_contra' => $row->points_against,
                            'promedio' => number_format((float) $row->points_average, 3, '.', ''),
                            'clasificacion' => $row->final_place,
                        ])->values(),
                    ])->values(),
                    'partidos' => $phase->matches->map(fn ($match): array => [
                        'id' => $match->id, 'zona_id' => $match->group_id,
                        'ronda' => $match->round_number, 'orden' => $match->bracket_order,
                        'etiqueta' => $match->label, 'jugador_1' => $match->playerOne?->player?->full_name,
                        'jugador_2' => $match->playerTwo?->player?->full_name, 'sets' => $match->set_scores,
                        'sets_reales' => [$match->player_one_actual_sets, $match->player_two_actual_sets],
                        'sets_computables' => [$match->player_one_computed_sets, $match->player_two_computed_sets],
                        'puntos' => [$match->player_one_points, $match->player_two_points],
                        'ganador' => $match->winner?->player?->full_name, 'mesa' => $match->table_number,
                        'horario' => $match->scheduled_at?->toIso8601String(), 'pase_automatico' => $match->is_bye,
                        'siguiente_partido_id' => $match->next_match_id,
                        'siguiente_posicion' => $match->next_match_position,
                        'estado' => $match->status,
                    ])->values(),
                ])->values(),
            ],
        ])->withHeaders(['Cache-Control' => 'no-store', 'Access-Control-Allow-Origin' => '*']);
    }
}
