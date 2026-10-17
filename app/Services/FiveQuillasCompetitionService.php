<?php

namespace App\Services;

use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasGroup;
use App\Models\FiveQuillasPhase;
use App\Models\GeneralRanking;
use App\Models\Player;
use App\Models\Tournament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FiveQuillasCompetitionService
{
    public function initialize(Tournament $tournament): FiveQuillasCompetition
    {
        $tournament->loadMissing(['discipline', 'type']);
        if ($tournament->discipline?->code !== 'five_quillas') {
            throw ValidationException::withMessages(['tournament' => 'La organización visual está disponible solamente para torneos de 5 Quillas.']);
        }

        return DB::transaction(function () use ($tournament): FiveQuillasCompetition {
            $competition = FiveQuillasCompetition::query()->firstOrCreate(
                ['tournament_id' => $tournament->id],
                ['public_slug' => $this->uniqueSlug($tournament), 'status' => 'draft', 'is_public' => false],
            );

            if ($competition->phases()->doesntExist()) {
                foreach ($this->phaseTemplate($tournament) as $phase) {
                    $competition->phases()->create($phase);
                }
            }

            $this->syncApprovedEntries($competition);

            return $competition->fresh(['phases', 'entries']);
        });
    }

    public function syncApprovedEntries(FiveQuillasCompetition $competition): int
    {
        $competition->loadMissing('tournament');
        $registrations = $competition->tournament->registrations()
            ->where('status', 'aprobado')
            ->with(['participants.player', 'slot'])
            ->get();
        $activePlayerIds = collect();

        foreach ($registrations as $registration) {
            if ($registration->slot && ! $registration->slot->is_active) {
                continue;
            }
            $participants = $registration->participants;
            if ($participants->isEmpty()) {
                $participants = collect($registration->participantIds())->map(function (int $playerId) {
                    return (object) ['id' => null, 'player_id' => $playerId];
                });
            }
            foreach ($participants as $participant) {
                if (! Player::query()->whereKey($participant->player_id)->exists()) {
                    continue;
                }
                $ranking = GeneralRanking::query()->where('player_id', $participant->player_id)->value('RG');
                $competition->entries()->updateOrCreate(
                    ['player_id' => $participant->player_id],
                    [
                        'tournament_registration_id' => $registration->id,
                        'registration_participant_id' => $participant->id ?? null,
                        'source_slot_id' => $registration->tournament_slot_id,
                        'national_ranking_snapshot' => $ranking,
                        'status' => 'eligible',
                    ],
                );
                $activePlayerIds->push((int) $participant->player_id);
            }
        }

        $competition->entries()->whereNotIn('player_id', $activePlayerIds->unique()->all())->update(['status' => 'withdrawn']);

        return $activePlayerIds->unique()->count();
    }

    public function createManualZones(FiveQuillasCompetition $competition, int $slotId, array $zoneRows): int
    {
        return DB::transaction(function () use ($competition, $slotId, $zoneRows): int {
            $phase = $competition->phases()->where('phase_type', 'zones')->where('is_enabled', true)->firstOrFail();
            $availableEntries = $competition->entries()
                ->where('source_slot_id', $slotId)
                ->whereIn('status', ['eligible', 'assigned'])
                ->get()->keyBy('id');

            $zones = collect($zoneRows)->map(function (array $row): array {
                $positions = collect(range(1, 4))->mapWithKeys(fn (int $position): array => [
                    $position => filled($row['player_'.$position] ?? null) ? (int) $row['player_'.$position] : null,
                ]);
                return ['name' => trim((string) ($row['name'] ?? '')), 'positions' => $positions];
            })->values();
            $selected = $zones->flatMap(fn (array $zone) => $zone['positions']->filter()->values());

            if ($zones->isEmpty()) {
                throw ValidationException::withMessages([
                    'zones' => 'Agregá al menos una zona.',
                ]);
            }
            foreach ($zones as $index => $zone) {
                if (! in_array($zone['positions']->filter()->count(), [3, 4], true)) {
                    throw ValidationException::withMessages(['zones' => 'La zona '.($index + 1).' debe tener 3 o 4 jugadores.']);
                }
            }
            if ($selected->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['zones' => 'Un jugador no puede ocupar dos lugares o integrar más de una zona.']);
            }
            if ($selected->diff($availableEntries->keys())->isNotEmpty()) {
                throw ValidationException::withMessages(['zones' => 'Todas las personas seleccionadas deben ser inscriptos aprobados del horario elegido.']);
            }

            $existing = $phase->groups()->where('tournament_slot_id', $slotId)->with('matches')->get();
            if ($existing->flatMap->matches->contains(fn ($match): bool => $match->status !== 'pending')) {
                throw ValidationException::withMessages([
                    'slot' => 'No se pueden regenerar las zonas porque ya existen partidos iniciados o programados.',
                ]);
            }

            $existing->each->delete();
            $firstOrder = (int) $phase->groups()->max('sort_order');

            foreach ($zones as $index => $zone) {
                $group = $phase->groups()->create([
                    'tournament_slot_id' => $slotId,
                    'name' => $zone['name'] ?: 'Zona '.($firstOrder + $index + 1),
                    'sort_order' => $firstOrder + $index + 1,
                    'status' => 'confirmed',
                    'is_public' => (bool) $competition->is_public,
                ]);

                foreach ($zone['positions']->filter() as $position => $entryId) {
                    $entry = $availableEntries->get($entryId);
                    $group->groupEntries()->create(['entry_id' => $entry->id, 'position' => $position]);
                    $entry->update(['status' => 'assigned']);
                }
                $this->createZoneBracket($phase, $group, $zone['positions']->map(fn ($entryId) => $entryId ? $availableEntries->get($entryId) : null));
            }

            $phase->update(['status' => 'ready']);
            return $zones->count();
        });
    }

    public function configureFormat(FiveQuillasCompetition $competition, array $phaseRows): int
    {
        return DB::transaction(function () use ($competition, $phaseRows): int {
            $rows = collect($phaseRows)->values();
            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['phases' => 'El torneo debe tener al menos una fase.']);
            }
            if ($rows->where('phase_type', 'zones')->where('is_enabled', true)->count() !== 1) {
                throw ValidationException::withMessages(['phases' => 'Debe existir exactamente una fase de zonas habilitada.']);
            }

            $existing = $competition->phases()->withCount(['groups', 'matches'])->get()->keyBy('id');
            $retainedIds = collect();
            $usedCodes = collect();

            foreach ($rows as $index => $row) {
                $phase = filled($row['id'] ?? null) ? $existing->get((int) $row['id']) : null;
                if ($phase && ($phase->groups_count > 0 || $phase->matches_count > 0)
                    && $phase->phase_type !== $row['phase_type']) {
                    throw ValidationException::withMessages([
                        'phases' => "No se puede cambiar el formato de {$phase->name} porque ya contiene zonas o partidos.",
                    ]);
                }

                $code = $phase?->code ?: $this->uniquePhaseCode($competition, (string) $row['name'], $usedCodes);
                $attributes = [
                    'code' => $code,
                    'name' => trim((string) $row['name']),
                    'phase_type' => $row['phase_type'],
                    'sort_order' => $index + 1,
                    'sets_to_play' => (int) $row['sets_to_play'],
                    'points_per_set' => (int) $row['points_per_set'],
                    'seeding_method' => $row['seeding_method'],
                    'split_by_slot' => (bool) ($row['split_by_slot'] ?? false),
                    'is_enabled' => (bool) ($row['is_enabled'] ?? true),
                    'settings' => [
                        'qualifiers' => filled($row['qualifiers'] ?? null) ? (int) $row['qualifiers'] : null,
                        'tables' => filled($row['tables'] ?? null) ? (int) $row['tables'] : null,
                        'estimated_minutes' => filled($row['estimated_minutes'] ?? null) ? (int) $row['estimated_minutes'] : null,
                        'rest_minutes' => filled($row['rest_minutes'] ?? null) ? (int) $row['rest_minutes'] : 0,
                        'start_time' => filled($row['start_time'] ?? null) ? $row['start_time'] : null,
                    ],
                ];

                if ($phase) {
                    $phase->update($attributes);
                } else {
                    $phase = $competition->phases()->create($attributes + ['status' => 'pending', 'is_public' => false]);
                }
                $retainedIds->push($phase->id);
                $usedCodes->push($code);
            }

            foreach ($existing->whereNotIn('id', $retainedIds) as $phase) {
                if ($phase->groups_count > 0 || $phase->matches_count > 0) {
                    $phase->update(['is_enabled' => false, 'is_public' => false]);
                } else {
                    $phase->delete();
                }
            }

            return $rows->where('is_enabled', true)->count();
        });
    }

    private function uniquePhaseCode(FiveQuillasCompetition $competition, string $name, $usedCodes): string
    {
        $base = Str::slug($name) ?: 'fase';
        $code = $base;
        $suffix = 2;
        while ($usedCodes->contains($code) || $competition->phases()->where('code', $code)->exists()) {
            $code = $base.'-'.$suffix++;
        }
        return $code;
    }

    private function createZoneBracket(FiveQuillasPhase $phase, FiveQuillasGroup $group, $entries): void
    {
        $public = $group->is_public;
        $secondPlace = $group->matches()->create([
            'phase_id' => $phase->id, 'round_number' => 3, 'bracket_order' => 1,
            'label' => 'Definición del segundo puesto', 'is_public' => $public,
        ]);
        $winnersFinal = $group->matches()->create([
            'phase_id' => $phase->id, 'round_number' => 2, 'bracket_order' => 1,
            'label' => 'Definición del primer puesto', 'loser_next_match_id' => $secondPlace->id,
            'loser_next_match_position' => 1, 'is_public' => $public,
        ]);
        $losersMatch = $group->matches()->create([
            'phase_id' => $phase->id, 'round_number' => 2, 'bracket_order' => 2,
            'label' => 'Partido de recuperación', 'next_match_id' => $secondPlace->id,
            'next_match_position' => 2, 'is_public' => $public,
        ]);
        $matchOne = $group->matches()->create([
            'phase_id' => $phase->id, 'round_number' => 1, 'bracket_order' => 1,
            'label' => 'Partido 1', 'player_one_entry_id' => $entries->get(1)?->id,
            'player_two_entry_id' => $entries->get(2)?->id, 'next_match_id' => $winnersFinal->id,
            'next_match_position' => 1, 'loser_next_match_id' => $losersMatch->id,
            'loser_next_match_position' => 1, 'is_public' => $public,
        ]);
        $matchTwo = $group->matches()->create([
            'phase_id' => $phase->id, 'round_number' => 1, 'bracket_order' => 2,
            'label' => 'Partido 2', 'player_one_entry_id' => $entries->get(3)?->id,
            'player_two_entry_id' => $entries->get(4)?->id, 'next_match_id' => $winnersFinal->id,
            'next_match_position' => 2, 'loser_next_match_id' => $losersMatch->id,
            'loser_next_match_position' => 2, 'is_public' => $public,
        ]);
        $this->resolveBye($matchOne);
        $this->resolveBye($matchTwo);
    }

    private function resolveBye($match): void
    {
        $players = collect([$match->player_one_entry_id, $match->player_two_entry_id])->filter();
        if ($players->count() !== 1) {
            return;
        }
        $winnerId = (int) $players->first();
        $match->update([
            'winner_entry_id' => $winnerId,
            'set_scores' => ['bye' => true, 'winner_sets' => 2, 'loser_sets' => 0, 'sets' => []],
            'is_bye' => true,
            'status' => 'completed',
        ]);
        $field = $match->next_match_position === 1 ? 'player_one_entry_id' : 'player_two_entry_id';
        $match->nextMatch()->update([$field => $winnerId]);
    }

    private function phaseTemplate(Tournament $tournament): array
    {
        $type = Str::lower(($tournament->type?->code ?? '').' '.($tournament->type?->name ?? ''));
        $circuit = Str::contains($type, 'circuit');

        if ($circuit) {
            return [
                $this->phase('zones', 'Zonas', 'zones', 1, 3, 'automatic_draw', true),
                $this->phase('slot-elimination', 'Eliminación por horario', 'slot_elimination', 2, 3, 'automatic_draw', true),
                $this->phase('national-play-in', 'Emparejamientos de Primera Nacional', 'play_in', 3, 3, 'national_ranking', true),
                $this->phase('national', 'Primera Nacional', 'bracket', 4, 5, 'national_ranking', true),
                $this->phase('master-final', 'Master / Etapa final', 'bracket', 5, 5, 'national_ranking', false),
            ];
        }

        return [
            $this->phase('zones', 'Zonas', 'zones', 1, 3, 'automatic_draw', true),
            $this->phase('intermediate', 'Fase intermedia', 'slot_elimination', 2, 3, 'previous_results', true),
            $this->phase('final', 'Cuadro final', 'bracket', 3, 3, 'number_draw', false),
        ];
    }

    private function phase(string $code, string $name, string $type, int $order, int $sets, string $seeding, bool $split): array
    {
        return [
            'code' => $code, 'name' => $name, 'phase_type' => $type, 'sort_order' => $order,
            'sets_to_play' => $sets, 'points_per_set' => 60, 'seeding_method' => $seeding,
            'split_by_slot' => $split, 'is_enabled' => true, 'status' => 'pending', 'is_public' => false,
        ];
    }

    private function uniqueSlug(Tournament $tournament): string
    {
        $base = Str::slug($tournament->name).'-'.$tournament->id;
        $slug = $base;
        $suffix = 2;
        while (FiveQuillasCompetition::query()->where('public_slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }
        return $slug;
    }
}
