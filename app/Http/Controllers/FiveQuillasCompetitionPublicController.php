<?php

namespace App\Http\Controllers;

use App\Models\FiveQuillasCompetition;
use App\Services\FiveQuillasCompetitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Entrega la representación JSON pública y normalizada de la competencia.
 */
class FiveQuillasCompetitionPublicController extends Controller
{
    /**
     * Atiende la solicitud después de resolver autorización y datos publicados.
     */
    public function __invoke(string $slug): JsonResponse
    {
        $competition = FiveQuillasCompetition::query()
            ->where('public_slug', $slug)
            ->where('is_public', true)
            ->with([
                'tournament',
                'tournament.type.publicationFederation',
                'tournament.venue.city.state.federation',
                'tournament.externalVenueState.federation',
                'tournament.tournamentModalities.modality',
                'phases' => fn ($query) => $query->where('is_public', true)->where('is_enabled', true),
                'phases.groups' => fn ($query) => $query->where('is_public', true),
                'phases.groups.slot:id,name,starts_at',
                'phases.groups.groupEntries.entry.player:id,first_name,last_name,club_id',
                'phases.groups.groupEntries.entry.player.club:id,name,short_name',
                'phases.matches' => fn ($query) => $query->where('is_public', true),
                'phases.matches.playerOne.player:id,first_name,last_name,photo_path',
                'phases.matches.playerTwo.player:id,first_name,last_name,photo_path',
                'phases.matches.winner.player:id,first_name,last_name,photo_path',
                'entries' => fn ($query) => $query->whereNotNull('final_position')->orderBy('final_position'),
                'entries.player:id,first_name,last_name,club_id,category_id,photo_path',
                'entries.player.club:id,name,short_name',
                'entries.player.category:id,name,code',
                'entries.registration:id,points,penalty_points',
            ])->firstOrFail();

        app(FiveQuillasCompetitionService::class)->ensureFinalStandingsOrder($competition);
        $competition->unsetRelation('entries')->load([
            'entries' => fn ($query) => $query->whereNotNull('final_position')->orderBy('final_position'),
            'entries.player:id,first_name,last_name,club_id,category_id,photo_path',
            'entries.player.club:id,name,short_name',
            'entries.player.category:id,name,code',
            'entries.registration:id,points,penalty_points',
        ]);

        $showFinalStandings = (bool) data_get($competition->settings, 'public.show_final_standings', true);
        $tournament = $competition->tournament;
        $logoPath = $tournament->publicationLogoPath();
        // En competencias históricas puede haber una fase concurrente que haya
        // quedado con un estado desactualizado. El cierre explícito de la
        // competencia también es una fuente válida para publicar al campeón.
        $tournamentCompleted = $competition->status === 'completed'
            || ! $competition->phases()->where('is_enabled', true)->where('status', '!=', 'completed')->exists();
        $finalPhase = $competition->phases
            ->where('phase_type', '!=', 'zones')
            ->sortByDesc('sort_order')
            ->first();
        $championEntry = $tournamentCompleted
            ? ($competition->entries->firstWhere('final_position', 1)
                ?: $finalPhase?->matches->where('status', 'completed')
                    ->sortByDesc(fn ($match): int => ((int) $match->round_number * 100000) + (int) $match->bracket_order)
                    ->first()?->winner)
            : null;
        $championPhoto = $championEntry?->player?->photo_path;

        return response()->json([
            'data' => [
                'slug' => $competition->public_slug,
                'status' => $competition->status,
                'torneo' => $competition->tournament->name,
                'sede' => $tournament->venueName(),
                'fecha' => $this->dateLabel($tournament->start_date, $tournament->end_date),
                'tipo_torneo' => $tournament->type?->name,
                'caracter' => $tournament->type?->is_official ? 'Oficial' : 'No oficial',
                'modalidad' => $tournament->tournamentModalities->pluck('modality.name')->filter()->implode(', '),
                'logo' => $logoPath ? Storage::disk('public_path')->url($logoPath) : null,
                'campeon' => $championEntry?->player ? [
                    'nombre' => $championEntry->player->full_name,
                    'foto' => $championPhoto ? Storage::disk('public_path')->url($championPhoto) : null,
                ] : null,
                'fecha_inicio' => $competition->tournament->start_date?->toDateString(),
                'fecha_fin' => $competition->tournament->end_date?->toDateString(),
                'informacion_disponible' => $competition->phases->contains(
                    fn ($phase): bool => $phase->groups->isNotEmpty() || $phase->matches->isNotEmpty()
                ),
                'mostrar_posiciones_finales' => $showFinalStandings,
                // La fuente de OBS consulta este mismo JSON cada pocos segundos.
                // Incluir su configuración permite aplicar cambios sin recargarla.
                'transmision' => [
                    'habilitada' => (bool) data_get($competition->settings, 'broadcast.enabled', false),
                    'todas_fases' => (bool) data_get($competition->settings, 'broadcast.all_phases', true),
                    'fases' => collect(data_get($competition->settings, 'broadcast.phase_ids', []))
                        ->map(fn ($id): int => (int) $id)->values(),
                    'todos_horarios_zonas' => (bool) data_get($competition->settings, 'broadcast.all_zone_slots', true),
                    'horarios_zonas' => collect(data_get($competition->settings, 'broadcast.zone_slot_keys', []))
                        ->map(fn ($key): string => (string) $key)->values(),
                    'segundos' => max(4, min(60, (int) data_get($competition->settings, 'broadcast.rotation_seconds', 8))),
                ],
                'posiciones_finales' => $showFinalStandings
                    ? $competition->entries->map(function ($entry) use ($competition): array {
                        $performance = app(FiveQuillasCompetitionService::class)
                            ->finalStandingPerformance($competition, (int) $entry->id);
                        return [
                        'posicion' => $entry->final_position,
                        'jugador' => $entry->player?->full_name,
                        'club' => $entry->player?->club?->short_name ?: $entry->player?->club?->name,
                        'categoria' => $entry->player?->category?->code ?: $entry->player?->category?->name,
                        'puntos' => $entry->registration?->points,
                        'penalizacion' => $entry->registration?->penalty_points,
                        'instancia' => $this->finalInstance($competition, $entry),
                        'diferencia_sets' => $performance['sets_difference'],
                        'promedio' => number_format($performance['average'], 3, '.', ''),
                    ]; })->values()
                    : [],
                'fases' => $competition->phases->map(fn ($phase): array => [
                    'id' => $phase->id,
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
                    'horarios_fase' => collect(data_get($phase->settings, 'schedules', []))
                        ->sortBy(fn (array $schedule, int $index): array => [
                            filled($schedule['starts_at'] ?? null)
                                ? \Illuminate\Support\Carbon::parse($schedule['starts_at'])->timestamp
                                : PHP_INT_MAX,
                            $index,
                        ])->values(),
                    'clasificados' => app(FiveQuillasCompetitionService::class)
                        ->qualifiedParticipantRows($phase),
                    'zonas' => $phase->groups->sortBy(fn ($group): array => [
                        $group->slot?->starts_at?->timestamp ?? PHP_INT_MAX,
                        (int) $group->sort_order,
                    ])->map(fn ($group): array => [
                        'id' => $group->id, 'nombre' => $group->name, 'estado' => $group->status,
                        'horario' => $group->slot?->name,
                        'horario_clave' => $group->tournament_slot_id ? 'slot_'.$group->tournament_slot_id : 'without_slot',
                        'inicio' => $this->dateTimeLabel($group->slot?->starts_at),
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
                        'placeholder_1' => $this->bracketSlotPlaceholder($phase, $match, 0),
                        'numero_1' => $match->round_number === 1
                            ? data_get($phase->settings, 'bracket_pairs.'.($match->bracket_order - 1).'.0')
                            : $match->playerOne?->seed,
                        'jugador_2' => $match->playerTwo?->player?->full_name,
                        'placeholder_2' => $this->bracketSlotPlaceholder($phase, $match, 1),
                        'numero_2' => $match->round_number === 1
                            ? data_get($phase->settings, 'bracket_pairs.'.($match->bracket_order - 1).'.1')
                            : $match->playerTwo?->seed, 'sets' => $match->set_scores,
                        'sets_reales' => [$match->player_one_actual_sets, $match->player_two_actual_sets],
                        'sets_computables' => [$match->player_one_computed_sets, $match->player_two_computed_sets],
                        'puntos' => [$match->player_one_points, $match->player_two_points],
                        'ganador' => $match->winner?->player?->full_name, 'mesa' => $match->table_number,
                        'horario' => $this->dateTimeLabel($match->scheduled_at), 'pase_automatico' => $match->is_bye,
                        'siguiente_partido_id' => $match->next_match_id,
                        'siguiente_posicion' => $match->next_match_position,
                        'estado' => $match->status,
                    ])->values(),
                ])->values(),
            ],
        ])->withHeaders(['Cache-Control' => 'no-store', 'Access-Control-Allow-Origin' => '*']);
    }

    private function dateLabel($start, $end): string
    {
        if (! $start) {
            return 'Sin fecha';
        }

        return $end && ! $end->isSameDay($start)
            ? $start->format('d/m/Y').' al '.$end->format('d/m/Y')
            : $start->format('d/m/Y');
    }

    private function bracketSlotPlaceholder($phase, $match, int $side): string
    {
        if ($phase->phase_type !== 'bracket' || (int) $match->round_number > 1) {
            return 'A designar';
        }
        $seed = (int) data_get(
            $phase->settings,
            'bracket_pairs.'.max(0, (int) $match->bracket_order - 1).'.'.$side,
            PHP_INT_MAX,
        );
        $expected = (int) data_get($phase->settings, 'expected_participant_count', 0);

        return $expected > 0 && $seed <= $expected ? 'A designar' : 'LIBRE';
    }

    private function dateTimeLabel($dateTime): ?string
    {
        return $dateTime?->format('d/m/Y · H:i');
    }

    private function finalInstance(FiveQuillasCompetition $competition, $entry): string
    {
        return app(FiveQuillasCompetitionService::class)->finalInstanceForEntry($competition, $entry);
    }

    private function entryPerformance(FiveQuillasCompetition $competition, int $entryId): array
    {
        $setsFor = 0; $setsAgainst = 0; $pointsFor = 0; $pointsAgainst = 0;
        foreach ($competition->phases->flatMap->matches->where('status', 'completed') as $match) {
            $isOne = (int) $match->player_one_entry_id === $entryId;
            $isTwo = (int) $match->player_two_entry_id === $entryId;
            if (! $isOne && ! $isTwo) { continue; }
            $setsFor += $isOne ? (int) $match->player_one_computed_sets : (int) $match->player_two_computed_sets;
            $setsAgainst += $isOne ? (int) $match->player_two_computed_sets : (int) $match->player_one_computed_sets;
            $pointsFor += $isOne ? (int) $match->player_one_points : (int) $match->player_two_points;
            $pointsAgainst += $isOne ? (int) $match->player_two_points : (int) $match->player_one_points;
        }
        return [
            'sets_difference' => $setsFor - $setsAgainst,
            'average' => $pointsAgainst > 0 ? $pointsFor / $pointsAgainst : 0,
        ];
    }
}
