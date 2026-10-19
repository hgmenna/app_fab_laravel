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
                'entries' => fn ($query) => $query->whereNotNull('final_position')->orderBy('final_position'),
                'entries.player:id,first_name,last_name,club_id,category_id',
                'entries.player.club:id,name,short_name',
                'entries.player.category:id,name,code',
                'entries.registration:id,points,penalty_points',
            ])->firstOrFail();

        $showFinalStandings = (bool) data_get($competition->settings, 'public.show_final_standings', true);

        return response()->json([
            'data' => [
                'slug' => $competition->public_slug,
                'status' => $competition->status,
                'torneo' => $competition->tournament->name,
                'fecha_inicio' => $competition->tournament->start_date?->toDateString(),
                'fecha_fin' => $competition->tournament->end_date?->toDateString(),
                'informacion_disponible' => $competition->phases->contains(
                    fn ($phase): bool => $phase->groups->isNotEmpty() || $phase->matches->isNotEmpty()
                ),
                'mostrar_posiciones_finales' => $showFinalStandings,
                'posiciones_finales' => $showFinalStandings
                    ? $competition->entries->map(fn ($entry): array => [
                        'posicion' => $entry->final_position,
                        'jugador' => $entry->player?->full_name,
                        'club' => $entry->player?->club?->short_name ?: $entry->player?->club?->name,
                        'categoria' => $entry->player?->category?->code ?: $entry->player?->category?->name,
                        'puntos' => $entry->registration?->points,
                        'penalizacion' => $entry->registration?->penalty_points,
                        'instancia' => $this->finalInstance($competition, $entry),
                    ])->values()
                    : [],
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
                        'etiqueta' => $match->label === 'Partido de recuperación' ? 'Perdedores' : $match->label,
                        'jugador_1' => $match->playerOne?->player?->full_name,
                        'numero_1' => $match->playerOne?->seed ?? ($match->round_number === 1 ? data_get($phase->settings, 'bracket_pairs.'.($match->bracket_order - 1).'.0') : null),
                        'jugador_2' => $match->playerTwo?->player?->full_name,
                        'numero_2' => $match->playerTwo?->seed ?? ($match->round_number === 1 ? data_get($phase->settings, 'bracket_pairs.'.($match->bracket_order - 1).'.1') : null), 'sets' => $match->set_scores,
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

    private function finalInstance(FiveQuillasCompetition $competition, $entry): string
    {
        $lastMatch = $competition->phases->where('phase_type', '!=', 'zones')->flatMap->matches
            ->filter(fn ($match): bool => in_array((int) $entry->id, [
                (int) $match->player_one_entry_id, (int) $match->player_two_entry_id,
            ], true))
            ->sortByDesc(fn ($match): string => sprintf('%05d-%05d', $match->phase->sort_order, $match->round_number))
            ->first();
        if ($lastMatch) {
            return preg_replace('/\s+\d+$/', '', (string) $lastMatch->label) ?: $lastMatch->phase->name;
        }
        $zoneRow = $competition->phases->flatMap->groups->flatMap->groupEntries
            ->firstWhere('entry_id', $entry->id);
        return $zoneRow ? 'Zonas · '.$zoneRow->final_place.'.º puesto' : 'Sin instancia';
    }
}
