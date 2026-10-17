<?php

namespace App\Services;

use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasGroup;
use App\Models\FiveQuillasMatch;
use App\Models\FiveQuillasPhase;
use App\Models\GeneralRanking;
use App\Models\Player;
use App\Models\Tournament;
use App\Services\TournamentScoringService;
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
                $this->recalculateGroupStatistics($group->id);
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
            if ($rows->where('phase_type', 'zones')->where('is_enabled', true)->count() > 1) {
                throw ValidationException::withMessages(['phases' => 'Puede existir como máximo una fase de zonas habilitada.']);
            }

            $existing = $competition->phases()->withCount(['groups', 'matches'])->get()->keyBy('id');
            $retainedIds = collect();
            $usedCodes = collect();
            $lastEnabledIndex = $rows->search(fn (array $row, int $index): bool =>
                (bool) ($row['is_enabled'] ?? true)
                && $rows->slice($index + 1)->every(fn (array $next): bool => ! (bool) ($next['is_enabled'] ?? true))
            );

            foreach ($rows as $index => $row) {
                $allowedSeeding = match ($row['phase_type']) {
                    'zones' => ['manual', 'national_ranking'],
                    'slot_elimination' => ['manual', 'previous_results', 'automatic_draw'],
                    'play_in' => ['manual', 'national_ranking', 'previous_results'],
                    default => ['manual', 'previous_results', 'national_ranking', 'number_draw'],
                };
                if (! in_array($row['seeding_method'], $allowedSeeding, true)) {
                    throw ValidationException::withMessages([
                        'phases' => 'El método de ubicación elegido no corresponde a la fase '.($index + 1).'.',
                    ]);
                }
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
                        'qualifiers' => $index === $lastEnabledIndex
                            ? null
                            : (filled($row['qualifiers'] ?? null) ? (int) $row['qualifiers'] : null),
                        'tables' => filled($row['tables'] ?? null) ? (int) $row['tables'] : null,
                        'estimated_minutes' => filled($row['estimated_minutes'] ?? null) ? (int) $row['estimated_minutes'] : null,
                        'rest_minutes' => filled($row['rest_minutes'] ?? null) ? (int) $row['rest_minutes'] : 0,
                        'bracket_size' => $row['phase_type'] === 'bracket' && filled($row['bracket_size'] ?? null)
                            ? (int) $row['bracket_size']
                            : null,
                        'bracket_pairs' => $row['phase_type'] === 'bracket' && filled($row['bracket_size'] ?? null)
                            ? $this->officialBracketPairs((int) $row['bracket_size'])
                            : [],
                        'stop_after_rounds' => data_get($phase?->settings, 'stop_after_rounds'),
                        'schedules' => $row['phase_type'] === 'zones'
                            ? []
                            : collect($row['schedules'] ?? [])->map(fn (array $schedule): array => [
                                'name' => trim((string) ($schedule['name'] ?? '')),
                                'starts_at' => filled($schedule['starts_at'] ?? null) ? $schedule['starts_at'] : null,
                                'round_number' => max(1, (int) ($schedule['round_number'] ?? 1)),
                                'bracket_section' => trim((string) ($schedule['bracket_section'] ?? '')) ?: null,
                                'from_match' => filled($schedule['from_match'] ?? null) ? max(1, (int) $schedule['from_match']) : null,
                                'to_match' => filled($schedule['to_match'] ?? null) ? max(1, (int) $schedule['to_match']) : null,
                                'tables' => filled($schedule['tables'] ?? null) ? (int) $schedule['tables'] : null,
                            ])->filter(fn (array $schedule): bool => $schedule['name'] !== '')->values()->all(),
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

    public function applyTournamentTypeTemplate(FiveQuillasCompetition $competition): int
    {
        return DB::transaction(function () use ($competition): int {
            if ($competition->phases()->whereHas('groups')->exists()
                || $competition->phases()->whereHas('matches')->exists()) {
                throw ValidationException::withMessages([
                    'format' => 'No se puede reemplazar la plantilla porque ya existen zonas o partidos cargados.',
                ]);
            }
            $competition->loadMissing('tournament.type');
            $competition->phases()->delete();
            foreach ($this->phaseTemplate($competition->tournament) as $phase) {
                $competition->phases()->create($phase);
            }
            return $competition->phases()->count();
        });
    }

    public function scheduleConfiguredMatches(FiveQuillasPhase $phase, int $scheduleIndex): int
    {
        return DB::transaction(function () use ($phase, $scheduleIndex): int {
            $schedule = data_get($phase->settings, "schedules.{$scheduleIndex}");
            if (! is_array($schedule)) {
                throw ValidationException::withMessages(['schedule' => 'El horario seleccionado no existe.']);
            }
            if (! filled($schedule['starts_at'] ?? null)) {
                throw ValidationException::withMessages(['schedule' => 'Indicá la fecha y hora de inicio del horario.']);
            }
            $tables = (int) ($schedule['tables'] ?? data_get($phase->settings, 'tables', 0));
            $minutes = (int) data_get($phase->settings, 'estimated_minutes', 0);
            if ($tables < 1 || $minutes < 1) {
                throw ValidationException::withMessages([
                    'schedule' => 'Configurá al menos una mesa y la duración estimada de cada partido.',
                ]);
            }

            $round = max(1, (int) ($schedule['round_number'] ?? 1));
            $matches = $phase->matches()->where('round_number', $round)
                ->with(['playerOne', 'playerTwo'])->orderBy('bracket_order')->get();
            if ($round === 1 && count(data_get($phase->settings, 'bracket_pairs', []))) {
                $order = collect(data_get($phase->settings, 'bracket_pairs'))->mapWithKeys(
                    fn (array $pair, int $index): array => [min($pair).'-'.max($pair) => $index]
                );
                $matches = $matches->sortBy(function ($match) use ($order): int {
                    $one = (int) ($match->playerOne?->seed ?? 0);
                    $two = (int) ($match->playerTwo?->seed ?? 0);
                    return $order->get(min($one, $two).'-'.max($one, $two), 10000 + $match->bracket_order);
                })->values();
            }
            $from = filled($schedule['from_match'] ?? null) ? (int) $schedule['from_match'] : 1;
            $to = filled($schedule['to_match'] ?? null) ? (int) $schedule['to_match'] : $matches->count();
            $matches = $matches->slice($from - 1, max(0, $to - $from + 1))->values();
            if ($matches->isEmpty()) {
                throw ValidationException::withMessages([
                    'schedule' => "La fase todavía no tiene partidos creados para la ronda {$round}.",
                ]);
            }

            $start = \Illuminate\Support\Carbon::parse($schedule['starts_at']);
            foreach ($matches as $index => $match) {
                $batch = intdiv($index, $tables);
                $match->update([
                    'scheduled_at' => $start->copy()->addMinutes($batch * $minutes),
                    'table_number' => ($index % $tables) + 1,
                    'estimated_minutes' => $minutes,
                    'status' => $match->status === 'pending' ? 'scheduled' : $match->status,
                ]);
            }

            return $matches->count();
        });
    }

    public function preparePhase(FiveQuillasPhase $phase): int
    {
        return DB::transaction(function () use ($phase): int {
            $phase->loadMissing('competition.tournament.type');
            if ($phase->phase_type === 'zones') {
                throw ValidationException::withMessages(['phase' => 'Las zonas se confeccionan con la acción Cargar zonas.']);
            }
            if ($phase->matches()->exists()) {
                return $phase->matches()->count();
            }

            $previous = $phase->competition->phases()
                ->where('is_enabled', true)
                ->where('sort_order', '<', $phase->sort_order)
                ->orderByDesc('sort_order')->first();

            $source = $previous
                ? $this->qualifiedEntriesFromPhase($previous)
                : $phase->competition->entries()->whereIn('status', ['eligible', 'assigned', 'qualified'])->get();

            if ($source->isEmpty()) {
                throw ValidationException::withMessages(['phase' => 'La fase anterior todavía no tiene clasificados disponibles.']);
            }

            if ($phase->phase_type === 'slot_elimination') {
                return $this->createSlotElimination($phase, $previous);
            }

            if ($phase->phase_type === 'play_in') {
                return $this->createPlayIn($phase, $source);
            }

            $seeded = $this->seedBracketEntries($phase, $source);
            $count = $this->createEliminationBracket($phase, $seeded);
            $phase->update(['status' => 'ready']);

            return $count;
        });
    }

    private function createSlotElimination(FiveQuillasPhase $phase, ?FiveQuillasPhase $previous): int
    {
        if (! $previous || $previous->phase_type !== 'zones') {
            throw ValidationException::withMessages(['phase' => 'La eliminación por horario requiere una fase de zonas anterior.']);
        }

        $groups = $previous->groups()->with(['groupEntries.entry'])->orderBy('tournament_slot_id')->orderBy('sort_order')->get()
            ->groupBy('tournament_slot_id');
        $order = 0;
        foreach ($groups as $slotGroups) {
            $slotGroups = $slotGroups->values();
            for ($index = 0; $index < $slotGroups->count(); $index += 2) {
                $left = $slotGroups->get($index);
                $right = $slotGroups->get($index + 1);
                if (! $right) { continue; }
                $leftRows = $left->groupEntries->keyBy('final_place');
                $rightRows = $right->groupEntries->keyBy('final_place');
                foreach ([[$leftRows->get(1), $rightRows->get(2)], [$rightRows->get(1), $leftRows->get(2)]] as $pair) {
                    if (! $pair[0] || ! $pair[1]) { continue; }
                    $order++;
                    $phase->matches()->create([
                        'round_number' => 1, 'bracket_order' => $order,
                        'label' => 'Cruce por horario '.$order,
                        'player_one_entry_id' => $pair[0]->entry_id,
                        'player_two_entry_id' => $pair[1]->entry_id,
                        'is_public' => (bool) $phase->is_public,
                    ]);
                }
            }
        }
        if ($order === 0) {
            throw ValidationException::withMessages(['phase' => 'Finalizá y clasificá las zonas antes de crear los cruces.']);
        }
        $phase->update(['status' => 'ready']);
        return $order;
    }

    private function createPlayIn(FiveQuillasPhase $phase, $source): int
    {
        $ordered = $source->sortBy(fn ($entry): int => $entry->national_ranking_snapshot ?: PHP_INT_MAX)->values();
        $target = max(1, (int) data_get($phase->settings, 'qualifiers', 32));
        $excess = max(0, $ordered->count() - $target);
        if ($excess === 0) {
            $phase->update(['status' => 'completed', 'settings' => array_merge($phase->settings ?? [], [
                'qualified_entry_ids' => $ordered->pluck('id')->all(),
            ])]);
            return 0;
        }
        $playInPlayers = $ordered->slice($ordered->count() - ($excess * 2))->values();
        for ($index = 0; $index < $excess; $index++) {
            $phase->matches()->create([
                'round_number' => 1, 'bracket_order' => $index + 1,
                'label' => 'Emparejamiento '.($index + 1),
                'player_one_entry_id' => $playInPlayers->get($index)?->id,
                'player_two_entry_id' => $playInPlayers->get($playInPlayers->count() - 1 - $index)?->id,
                'is_public' => (bool) $phase->is_public,
            ]);
        }
        $phase->update(['status' => 'ready', 'settings' => array_merge($phase->settings ?? [], [
            'play_in_bye_entry_ids' => $ordered->take($target - $excess)->pluck('id')->all(),
        ])]);
        return $excess;
    }

    private function seedBracketEntries(FiveQuillasPhase $phase, $source): array
    {
        $size = (int) data_get($phase->settings, 'bracket_size', 16);
        $source = collect($source)->unique('id')->values();
        $seeds = [];

        if ($phase->code === 'national') {
            $national = $phase->competition->entries()->with('player.category')
                ->whereNotIn('id', $source->pluck('id'))
                ->get()->filter(function ($entry): bool {
                    $category = Str::lower(($entry->player?->category?->code ?? '').' '.($entry->player?->category?->name ?? ''));
                    return Str::contains($category, ['1ra', '1°', 'primera', 'nacional']);
                })->sortBy(fn ($entry): int => $entry->national_ranking_snapshot ?: PHP_INT_MAX)->take(32)->values();
            $national->each(fn ($entry, $index) => $seeds[$index + 1] = $entry);
            $source->sortBy(fn ($entry): int => $entry->national_ranking_snapshot ?: PHP_INT_MAX)->values()
                ->each(fn ($entry, $index) => $seeds[33 + $index] = $entry);
        } elseif ($phase->code === 'master-final') {
            $masters = $phase->competition->entries()->with('player.category')
                ->whereNotIn('id', $source->pluck('id'))
                ->get()->filter(function ($entry): bool {
                    $category = Str::lower(($entry->player?->category?->code ?? '').' '.($entry->player?->category?->name ?? ''));
                    return Str::contains($category, 'master');
                })->sortBy(fn ($entry): int => $entry->national_ranking_snapshot ?: PHP_INT_MAX)->take(16)->values();
            $masters->each(fn ($entry, $index) => $seeds[$index + 1] = $entry);
            $source->take(16)->values()->each(fn ($entry, $index) => $seeds[17 + $index] = $entry);
        } else {
            $ordered = $phase->seeding_method === 'national_ranking'
                ? $source->sortBy(fn ($entry): int => $entry->national_ranking_snapshot ?: PHP_INT_MAX)->values()
                : $source->values();
            $ordered->take($size)->each(fn ($entry, $index) => $seeds[$index + 1] = $entry);
        }

        foreach ($seeds as $seed => $entry) {
            $entry->update(['seed' => $seed, 'status' => 'assigned']);
        }
        return $seeds;
    }

    private function createEliminationBracket(FiveQuillasPhase $phase, array $seeds): int
    {
        $size = (int) data_get($phase->settings, 'bracket_size', 16);
        $rounds = (int) log($size, 2);
        $roundsToCreate = min($rounds, max(1, (int) data_get($phase->settings, 'stop_after_rounds', $rounds)));
        $created = [];
        for ($round = $roundsToCreate; $round >= 1; $round--) {
            $count = intdiv($size, 2 ** $round);
            for ($order = 1; $order <= $count; $order++) {
                $next = $created[$round + 1][(int) ceil($order / 2)] ?? null;
                $created[$round][$order] = $phase->matches()->create([
                    'round_number' => $round,
                    'bracket_order' => $order,
                    'label' => $round === $rounds
                        ? 'Final'
                        : ($round === $roundsToCreate && $roundsToCreate < $rounds
                            ? 'Clasificación · Partido '.$order
                            : 'Ronda '.$round.' · Partido '.$order),
                    'next_match_id' => $next?->id,
                    'next_match_position' => $next ? ($order % 2 === 1 ? 1 : 2) : null,
                    'is_public' => (bool) $phase->is_public,
                ]);
            }
        }
        $pairs = data_get($phase->settings, 'bracket_pairs', $this->officialBracketPairs($size));
        foreach ($pairs as $index => $pair) {
            $match = $created[1][$index + 1];
            $match->update([
                'player_one_entry_id' => ($seeds[$pair[0]] ?? null)?->id,
                'player_two_entry_id' => ($seeds[$pair[1]] ?? null)?->id,
            ]);
            $this->resolveBye($match->fresh());
        }
        $this->resolveReadyByes($phase->matches()->pluck('id'));
        return $phase->matches()->count();
    }

    private function qualifiedEntriesFromPhase(FiveQuillasPhase $phase)
    {
        if ($phase->phase_type === 'zones') {
            return $phase->groups()->with('groupEntries.entry')->get()->flatMap(
                fn ($group) => $group->groupEntries->whereIn('final_place', [1, 2])->pluck('entry')
            )->filter()->unique('id')->values();
        }
        $stored = collect(data_get($phase->settings, 'qualified_entry_ids', []));
        $byes = collect(data_get($phase->settings, 'play_in_bye_entry_ids', []));
        $lastRound = (int) $phase->matches()->max('round_number');
        $winners = $phase->matches()->where('round_number', $lastRound)->where('status', 'completed')
            ->pluck('winner_entry_id')->filter();
        return $phase->competition->entries()->whereIn('id', $stored->merge($byes)->merge($winners)->unique())->get();
    }

    public function recordMatchResult(FiveQuillasMatch $match, array $scores): FiveQuillasMatch
    {
        return DB::transaction(function () use ($match, $scores): FiveQuillasMatch {
            $match->loadMissing(['phase', 'group']);
            if (! $match->player_one_entry_id || ! $match->player_two_entry_id) {
                throw ValidationException::withMessages(['scores' => 'El partido todavía no tiene los dos jugadores definidos.']);
            }
            if ($match->is_bye) {
                throw ValidationException::withMessages(['scores' => 'Un pase libre no requiere carga de resultado.']);
            }

            $bestOf = (int) $match->phase->sets_to_play;
            $winsNeeded = intdiv($bestOf, 2) + 1;
            $pointsPerSet = (int) $match->phase->points_per_set;
            $normalizedScores = collect($scores)->map(function (array $set, int $index) use ($pointsPerSet): array {
                $one = (int) ($set['player_one_points'] ?? 0);
                $two = (int) ($set['player_two_points'] ?? 0);
                if ($one < 0 || $two < 0 || max($one, $two) !== $pointsPerSet) {
                    throw ValidationException::withMessages([
                        'scores' => 'El ganador del set '.($index + 1)." debe alcanzar exactamente {$pointsPerSet} puntos.",
                    ]);
                }
                if ($one === $two) {
                    throw ValidationException::withMessages(['scores' => 'El set '.($index + 1).' no puede terminar empatado.']);
                }
                return ['player_one_points' => $one, 'player_two_points' => $two];
            })->values();

            $oneWins = $normalizedScores->filter(fn (array $set): bool => $set['player_one_points'] > $set['player_two_points'])->count();
            $twoWins = $normalizedScores->count() - $oneWins;
            if (max($oneWins, $twoWins) !== $winsNeeded || min($oneWins, $twoWins) >= $winsNeeded) {
                throw ValidationException::withMessages([
                    'scores' => "El partido al mejor de {$bestOf} sets termina cuando un jugador gana {$winsNeeded} sets.",
                ]);
            }

            $winnerIsOne = $oneWins > $twoWins;
            $loserActualSets = min($oneWins, $twoWins);
            $winnerComputedSets = $bestOf - $loserActualSets;
            $oneComputed = $winnerIsOne ? $winnerComputedSets : $loserActualSets;
            $twoComputed = $winnerIsOne ? $loserActualSets : $winnerComputedSets;
            $onePoints = $normalizedScores->sum('player_one_points');
            $twoPoints = $normalizedScores->sum('player_two_points');
            $winnerId = $winnerIsOne ? $match->player_one_entry_id : $match->player_two_entry_id;
            $loserId = $winnerIsOne ? $match->player_two_entry_id : $match->player_one_entry_id;

            $match->update([
                'set_scores' => ['bye' => false, 'sets' => $normalizedScores->all()],
                'player_one_actual_sets' => $oneWins,
                'player_two_actual_sets' => $twoWins,
                'player_one_computed_sets' => $oneComputed,
                'player_two_computed_sets' => $twoComputed,
                'player_one_points' => $onePoints,
                'player_two_points' => $twoPoints,
                'winner_entry_id' => $winnerId,
                'status' => 'completed',
            ]);

            $this->advanceEntry($match->next_match_id, $match->next_match_position, $winnerId);
            $this->advanceEntry($match->loser_next_match_id, $match->loser_next_match_position, $loserId);
            $this->resolveReadyByes(collect([$match->next_match_id, $match->loser_next_match_id])->filter()->unique());
            if ($match->group_id) {
                $this->recalculateGroupStatistics($match->group_id);
            }
            $this->completePhaseWhenReady($match->phase_id);

            return $match->fresh();
        });
    }

    private function completePhaseWhenReady(int $phaseId): void
    {
        $phase = FiveQuillasPhase::query()->with('competition.tournament.type')->findOrFail($phaseId);
        if ($phase->matches()->exists() && $phase->matches()->where('status', '!=', 'completed')->doesntExist()) {
            $phase->update(['status' => 'completed']);
            $next = $phase->competition->phases()->where('is_enabled', true)
                ->where('sort_order', '>', $phase->sort_order)->orderBy('sort_order')->first();
            if (! $next) {
                $phase->competition->update(['status' => 'completed']);
                $this->assignFinalPoints($phase);
            }
        } elseif ($phase->matches()->where('status', 'completed')->exists()) {
            $phase->update(['status' => 'in_progress']);
            $phase->competition()->update(['status' => 'in_progress']);
        }
    }

    private function assignFinalPoints(FiveQuillasPhase $phase): void
    {
        $tournament = $phase->competition->tournament;
        $tournament->loadMissing('type');
        if (! $tournament->type?->assigns_points || $phase->phase_type !== 'bracket') {
            return;
        }

        $matches = $phase->matches()->with(['playerOne.registration', 'playerTwo.registration', 'winner'])->get();
        $lastRound = (int) $matches->max('round_number');
        $final = $matches->where('round_number', $lastRound)->first();
        if (! $final?->winner_entry_id) { return; }

        $placements = collect([(int) $final->winner_entry_id => 1]);
        $finalLoser = $this->loserEntryId($final);
        if ($finalLoser) { $placements->put($finalLoser, 2); }
        $position = 3;
        for ($round = $lastRound - 1; $round >= 1; $round--) {
            foreach ($matches->where('round_number', $round)->sortBy('bracket_order') as $match) {
                $loser = $this->loserEntryId($match);
                if ($loser && ! $placements->has($loser)) { $placements->put($loser, $position++); }
            }
        }

        $rules = app(TournamentScoringService::class)->getRules($tournament);
        $entries = $phase->competition->entries()->with('registration')->whereIn('id', $placements->keys())->get()->keyBy('id');
        foreach ($placements as $entryId => $place) {
            $registration = $entries->get($entryId)?->registration;
            $rule = $this->ruleForPlacement($rules, $place);
            if (! $registration || ! $rule || $registration->points !== null) { continue; }
            if (app(TournamentScoringService::class)->usesOfficialInstances($tournament) && ! empty($rule['tournament_instance_id'])) {
                app(TournamentScoringService::class)->assignByTournamentInstance($registration, (int) $rule['tournament_instance_id']);
            } elseif (! empty($rule['code'])) {
                app(TournamentScoringService::class)->assignByCode($registration, (string) $rule['code']);
            }
        }
    }

    private function loserEntryId(FiveQuillasMatch $match): ?int
    {
        if (! $match->winner_entry_id) { return null; }
        $ids = collect([$match->player_one_entry_id, $match->player_two_entry_id])->filter()->map(fn ($id): int => (int) $id);
        return $ids->first(fn (int $id): bool => $id !== (int) $match->winner_entry_id);
    }

    private function ruleForPlacement(array $rules, int $place): ?array
    {
        foreach ($rules as $rule) {
            $description = Str::lower((string) ($rule['description'] ?? ''));
            preg_match_all('/\d+/', $description, $matches);
            $numbers = collect($matches[0] ?? [])->map(fn ($value): int => (int) $value)->values();
            if ($numbers->count() >= 2 && $place >= $numbers[0] && $place <= $numbers[1]) { return $rule; }
            if ($numbers->count() === 1 && $place === $numbers[0]) { return $rule; }
            if ($place === 1 && Str::contains($description, ['campeón', 'campeon', 'ganador'])) { return $rule; }
            if ($place === 2 && Str::contains($description, ['finalista', 'subcampeón', 'subcampeon'])) { return $rule; }
            if (in_array($place, [3, 4], true) && Str::contains($description, 'semifinal')) { return $rule; }
            if ($place >= 5 && $place <= 8 && Str::contains($description, 'cuartos')) { return $rule; }
        }
        return null;
    }

    private function advanceEntry(?int $matchId, ?int $position, int $entryId): void
    {
        if (! $matchId || ! in_array($position, [1, 2], true)) {
            return;
        }
        FiveQuillasMatch::query()->whereKey($matchId)->update([
            $position === 1 ? 'player_one_entry_id' : 'player_two_entry_id' => $entryId,
        ]);
    }

    private function resolveReadyByes($matchIds): void
    {
        foreach ($matchIds as $matchId) {
            $candidate = FiveQuillasMatch::query()->find($matchId);
            if (! $candidate || $candidate->status === 'completed') {
                continue;
            }
            $sourceMatches = FiveQuillasMatch::query()
                ->where('next_match_id', $candidate->id)
                ->orWhere('loser_next_match_id', $candidate->id)
                ->get();
            if ($sourceMatches->isNotEmpty() && $sourceMatches->every(fn ($source): bool => $source->status === 'completed')) {
                $this->resolveBye($candidate);
            }
        }
    }

    private function recalculateGroupStatistics(int $groupId): void
    {
        $group = FiveQuillasGroup::query()->with(['groupEntries', 'matches'])->findOrFail($groupId);
        foreach ($group->groupEntries as $row) {
            $wins = 0; $setsFor = 0; $setsAgainst = 0; $pointsFor = 0; $pointsAgainst = 0;
            foreach ($group->matches->where('status', 'completed') as $match) {
                $isOne = (int) $match->player_one_entry_id === (int) $row->entry_id;
                $isTwo = (int) $match->player_two_entry_id === (int) $row->entry_id;
                if (! $isOne && ! $isTwo) { continue; }
                $wins += (int) $match->winner_entry_id === (int) $row->entry_id ? 1 : 0;
                $setsFor += $isOne ? $match->player_one_computed_sets : $match->player_two_computed_sets;
                $setsAgainst += $isOne ? $match->player_two_computed_sets : $match->player_one_computed_sets;
                $pointsFor += $isOne ? $match->player_one_points : $match->player_two_points;
                $pointsAgainst += $isOne ? $match->player_two_points : $match->player_one_points;
            }
            $row->update([
                'match_wins' => $wins,
                'sets_difference' => $setsFor - $setsAgainst,
                'points_for' => $pointsFor,
                'points_against' => $pointsAgainst,
                'points_average' => $pointsAgainst > 0 ? round($pointsFor / $pointsAgainst, 3) : 0,
            ]);
        }

        if ($group->matches->isNotEmpty() && $group->matches->every(fn ($match): bool => $match->status === 'completed')) {
            $matchesByLabel = $group->matches->keyBy('label');
            $firstMatch = $matchesByLabel->get('Definición del primer puesto');
            $secondMatch = $matchesByLabel->get('Definición del segundo puesto');
            $recoveryMatch = $matchesByLabel->get('Partido de recuperación');
            $places = collect();
            if ($firstMatch?->winner_entry_id) { $places->put((int) $firstMatch->winner_entry_id, 1); }
            if ($secondMatch?->winner_entry_id) { $places->put((int) $secondMatch->winner_entry_id, 2); }
            $third = $secondMatch ? $this->loserEntryId($secondMatch) : null;
            if ($third) { $places->put($third, 3); }
            $fourth = $recoveryMatch ? $this->loserEntryId($recoveryMatch) : null;
            if ($fourth && ! $places->has($fourth)) { $places->put($fourth, 4); }

            $remaining = $group->groupEntries()->whereNotIn('entry_id', $places->keys())
                ->orderByDesc('match_wins')->orderByDesc('sets_difference')
                ->orderByDesc('points_average')->orderByDesc('points_for')->get();
            foreach ($remaining as $row) {
                $places->put((int) $row->entry_id, $places->count() + 1);
            }
            foreach ($group->groupEntries as $row) {
                $place = (int) $places->get((int) $row->entry_id);
                $row->update(['final_place' => $place]);
                $row->entry()->update(['status' => $place <= 2 ? 'qualified' : 'eliminated']);
            }
            $group->update(['status' => 'completed']);
            if ($group->phase->groups()->where('status', '!=', 'completed')->doesntExist()) {
                $group->phase()->update(['status' => 'completed']);
            }
        }
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
            'set_scores' => ['bye' => true, 'sets' => []],
            'player_one_actual_sets' => $match->player_one_entry_id ? intdiv((int) $match->phase()->value('sets_to_play'), 2) + 1 : 0,
            'player_two_actual_sets' => $match->player_two_entry_id ? intdiv((int) $match->phase()->value('sets_to_play'), 2) + 1 : 0,
            'player_one_computed_sets' => $match->player_one_entry_id ? (int) $match->phase()->value('sets_to_play') : 0,
            'player_two_computed_sets' => $match->player_two_entry_id ? (int) $match->phase()->value('sets_to_play') : 0,
            'is_bye' => true,
            'status' => 'completed',
        ]);
        $field = $match->next_match_position === 1 ? 'player_one_entry_id' : 'player_two_entry_id';
        $match->nextMatch()->update([$field => $winnerId]);
    }

    private function phaseTemplate(Tournament $tournament): array
    {
        $format = $tournament->type?->competition_format ?: 'zones_final';

        if ($format === 'circuit_argentino') {
            return [
                $this->phase('zones', 'Zonas', 'zones', 1, 3, 'manual', true),
                $this->phase('slot-elimination', 'Eliminación por horario', 'slot_elimination', 2, 3, 'automatic_draw', true),
                $this->phase('national-play-in', 'Emparejamientos de Primera Nacional', 'play_in', 3, 3, 'national_ranking', true),
                $this->phase('national', 'Primera Nacional', 'bracket', 4, 5, 'national_ranking', true, [
                    'bracket_size' => 64,
                    'stop_after_rounds' => 2,
                    'bracket_pairs' => $this->officialBracketPairs(64),
                    'schedules' => [
                        ['name' => 'Horario 1', 'starts_at' => null, 'round_number' => 1, 'bracket_section' => '1-64 a 61-4', 'from_match' => 1, 'to_match' => 16, 'tables' => null],
                        ['name' => 'Horario 2', 'starts_at' => null, 'round_number' => 1, 'bracket_section' => '3-62 a 2-63', 'from_match' => 17, 'to_match' => 32, 'tables' => null],
                    ],
                ]),
                $this->phase('master-final', 'Master / Etapa final', 'bracket', 5, 5, 'national_ranking', false, [
                    'bracket_size' => 32,
                    'bracket_pairs' => $this->officialBracketPairs(32),
                ]),
            ];
        }

        if ($format === 'direct_elimination') {
            return [
                $this->phase('final', 'Cuadro eliminatorio', 'bracket', 1, 3, 'number_draw', false, [
                    'bracket_size' => 16,
                    'bracket_pairs' => $this->officialBracketPairs(16),
                ]),
            ];
        }

        if ($format === 'zones_final' || $format === 'custom') {
            return [
                $this->phase('zones', 'Zonas', 'zones', 1, 3, 'manual', true),
                $this->phase('final', 'Cuadro final', 'bracket', 2, 3, 'number_draw', false, [
                    'bracket_size' => 16,
                    'bracket_pairs' => $this->officialBracketPairs(16),
                ]),
            ];
        }

        return [
            $this->phase('zones', 'Zonas', 'zones', 1, 3, 'manual', true),
            $this->phase('intermediate', 'Fase intermedia', 'slot_elimination', 2, 3, 'previous_results', true),
            $this->phase('final', 'Cuadro final', 'bracket', 3, 3, 'number_draw', false, [
                'bracket_size' => 16,
                'bracket_pairs' => $this->officialBracketPairs(16),
            ]),
        ];
    }

    private function officialBracketPairs(int $size): array
    {
        return match ($size) {
            16 => [[1,16],[9,8],[5,12],[13,4],[3,14],[11,6],[7,10],[15,2]],
            32 => [[1,32],[16,17],[9,24],[25,8],[5,28],[12,21],[13,20],[29,4],
                [3,30],[14,19],[11,22],[27,6],[7,26],[10,23],[15,18],[31,2]],
            64 => [[1,64],[32,33],[17,48],[49,16],[9,56],[24,41],[25,40],[57,8],
                [5,60],[28,37],[12,53],[21,44],[13,52],[20,45],[29,36],[61,4],
                [3,62],[30,35],[19,46],[51,14],[11,54],[43,22],[27,38],[59,6],
                [7,58],[39,26],[23,42],[55,10],[15,50],[47,18],[31,34],[63,2]],
            default => throw ValidationException::withMessages([
                'phases' => 'El cuadro debe tener 16, 32 o 64 posiciones.',
            ]),
        };
    }

    private function phase(string $code, string $name, string $type, int $order, int $sets, string $seeding, bool $split, array $settings = []): array
    {
        if ($type !== 'zones' && ! array_key_exists('schedules', $settings)) {
            $settings['schedules'] = [[
                'name' => 'Horario principal',
                'starts_at' => null,
                'round_number' => 1,
                'bracket_section' => null,
                'from_match' => null,
                'to_match' => null,
                'tables' => null,
            ]];
        }

        return [
            'code' => $code, 'name' => $name, 'phase_type' => $type, 'sort_order' => $order,
            'sets_to_play' => $sets, 'points_per_set' => 60, 'seeding_method' => $seeding,
            'split_by_slot' => $split, 'is_enabled' => true, 'status' => 'pending', 'is_public' => false,
            'settings' => $settings,
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
