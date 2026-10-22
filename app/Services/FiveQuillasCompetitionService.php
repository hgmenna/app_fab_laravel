<?php

namespace App\Services;

use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasEntry;
use App\Models\FiveQuillasGroup;
use App\Models\FiveQuillasGroupEntry;
use App\Models\FiveQuillasMatch;
use App\Models\FiveQuillasPhase;
use App\Models\Category;
use App\Models\GeneralRanking;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
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
            ->with(['participants.player.category', 'slot'])
            ->get();
        $phases = $competition->phases()->where('is_enabled', true)->orderBy('sort_order')->get();
        $fallbackPhase = $phases->first();
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
                $rankingRow = GeneralRanking::query()->where('player_id', $participant->player_id)
                    ->first(['RG', 'category']);
                $ranking = $rankingRow?->RG;
                $player = $participant->player ?? Player::query()->with('category')->find($participant->player_id);
                $entryPhase = $this->entryPhaseForRegistration(
                    $phases,
                    $player,
                    $registration->slot?->name,
                ) ?: $fallbackPhase;
                $entry = $competition->entries()->firstOrNew(
                    ['player_id' => $participant->player_id],
                );
                $entry->fill([
                    'tournament_registration_id' => $registration->id,
                    'registration_participant_id' => $participant->id ?? null,
                    'source_slot_id' => $registration->tournament_slot_id,
                    'entry_phase_id' => $entryPhase?->id,
                    'national_ranking_snapshot' => $ranking,
                ]);
                if (! $entry->exists || $entry->status === 'withdrawn') {
                    $entry->status = 'eligible';
                }
                $entry->save();
                $activePlayerIds->push((int) $participant->player_id);
            }
        }

        $competition->entries()->whereNotIn('player_id', $activePlayerIds->unique()->all())->update(['status' => 'withdrawn']);

        return $activePlayerIds->unique()->count();
    }

    public function refreshEntryPhaseAssignments(FiveQuillasCompetition $competition): void
    {
        $phases = $competition->phases()->where('is_enabled', true)->orderBy('sort_order')->get();
        $entries = $competition->entries()->with(['player.category', 'sourceSlot'])->get();
        $changed = false;

        foreach ($entries as $entry) {
            $phase = $this->entryPhaseForRegistration(
                $phases,
                $entry->player,
                $entry->sourceSlot?->name,
            );
            if ($phase && (int) $entry->entry_phase_id !== (int) $phase->id) {
                $entry->update(['entry_phase_id' => $phase->id]);
                $changed = true;
            }
        }

        if ($changed) {
            $phases->where('phase_type', 'bracket')->each(function ($phase): void {
                $settings = $phase->settings ?? [];
                unset($settings['participant_seed_overrides']);
                $phase->update(['settings' => $settings]);
            });
        }
    }

    public function createManualZones(FiveQuillasCompetition $competition, int $slotId, array $zoneRows): int
    {
        return DB::transaction(function () use ($competition, $slotId, $zoneRows): int {
            $phase = $competition->phases()->where('phase_type', 'zones')->where('is_enabled', true)->firstOrFail();
            $previousPhaseStatus = $phase->status;
            $existing = $phase->groups()->where('tournament_slot_id', $slotId)
                ->with(['matches', 'groupEntries'])->get();
            $lockedGroups = $existing->filter(
                fn ($group): bool => $group->matches->contains(fn ($match): bool => $match->status !== 'pending')
            );
            $editableGroups = $existing->diff($lockedGroups);
            $lockedEntryIds = $lockedGroups->flatMap->groupEntries->pluck('entry_id')->unique();

            $availableEntries = $competition->entries()
                ->where('source_slot_id', $slotId)
                ->whereIn('status', ['eligible', 'assigned'])
                ->whereNotIn('id', $lockedEntryIds)
                ->get()->keyBy('id');

            $zones = collect($zoneRows)
                ->reject(fn (array $row): bool => (bool) ($row['locked'] ?? false))
                ->map(function (array $row): array {
                $positions = collect(range(1, 4))->mapWithKeys(fn (int $position): array => [
                    $position => filled($row['player_'.$position] ?? null) ? (int) $row['player_'.$position] : null,
                ]);
                return ['name' => trim((string) ($row['name'] ?? '')), 'positions' => $positions];
            })->values();
            $selected = $zones->flatMap(fn (array $zone) => $zone['positions']->filter()->values());

            if ($zones->isEmpty() && $lockedGroups->isNotEmpty()) {
                return 0;
            }
            if ($zones->isEmpty()) {
                throw ValidationException::withMessages(['zones' => 'Agregá al menos una zona.']);
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

            $editableEntryIds = $editableGroups->flatMap->groupEntries->pluck('entry_id')->unique();
            $competition->entries()->whereIn('id', $editableEntryIds)->update(['status' => 'eligible']);
            $editableGroups->each->delete();
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
                    $entry->update([
                        'status' => 'assigned',
                        'entry_phase_id' => $phase->id,
                    ]);
                }
                $this->createZoneBracket($phase, $group, $zone['positions']->map(fn ($entryId) => $entryId ? $availableEntries->get($entryId) : null));
                $this->recalculateGroupStatistics($group->id);
            }

            $phase->update(['status' => $previousPhaseStatus === 'in_progress' ? 'in_progress' : 'ready']);
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
            $assignedCategories = $rows->flatMap(fn (array $row): array => $row['entry_category_ids'] ?? [])
                ->filter()->map(fn ($id): int => (int) $id);
            if ($assignedCategories->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'phases' => 'Una categoría de jugadores puede tener una sola fase de ingreso en el torneo.',
                ]);
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
                $qualifiers = filled($row['qualifiers'] ?? null) ? (int) $row['qualifiers'] : null;
                $bracketSize = filled($row['bracket_size'] ?? null) ? (int) $row['bracket_size'] : null;
                $directEntrySeeds = filled($row['direct_entry_seed_count'] ?? null)
                    ? (int) $row['direct_entry_seed_count']
                    : 0;
                if (($row['phase_type'] ?? null) === 'bracket'
                    && $bracketSize
                    && $directEntrySeeds > $bracketSize) {
                    throw ValidationException::withMessages([
                        'phases' => 'Las posiciones reservadas para ingreso directo no pueden superar el tamaño del cuadro en la fase '.($index + 1).'.',
                    ]);
                }
                if (($row['phase_type'] ?? null) === 'bracket'
                    && $index !== $lastEnabledIndex
                    && $qualifiers !== null
                    && ($qualifiers < 1 || ($qualifiers & ($qualifiers - 1)) !== 0)) {
                    throw ValidationException::withMessages([
                        'phases' => 'En una fase de eliminación directa, la cantidad de clasificados debe ser 1, 2, 4, 8, 16, 32, 64 o 128 para conservar una grilla eliminatoria válida.',
                    ]);
                }
                if ((bool) ($row['progressive_fill'] ?? false)
                    && ($row['seeding_method'] ?? null) !== 'number_draw') {
                    throw ValidationException::withMessages([
                        'phases' => 'La carga progresiva requiere la ubicación por sorteo presencial para conservar fija la posición de cada jugador.',
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
                        'previous_phase_id' => filled($row['previous_phase_id'] ?? null)
                            ? (int) $row['previous_phase_id']
                            : null,
                        'next_phase_id' => filled($row['next_phase_id'] ?? null)
                            ? (int) $row['next_phase_id']
                            : null,
                        'progressive_fill' => (bool) ($row['progressive_fill'] ?? false),
                        'entry_category_ids' => collect($row['entry_category_ids'] ?? [])->filter()
                            ->map(fn ($id): int => (int) $id)->unique()->values()->all(),
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
                        'slot_crossings' => data_get($phase?->settings, 'slot_crossings', []),
                        'participant_seed_overrides' => data_get($phase?->settings, 'participant_seed_overrides', []),
                        'direct_entry_seed_count' => ($row['phase_type'] ?? null) === 'bracket'
                            && filled($row['direct_entry_seed_count'] ?? null)
                                ? max(0, (int) $row['direct_entry_seed_count'])
                                : data_get($phase?->settings, 'direct_entry_seed_count'),
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

            $enabledPhases = $competition->phases()->whereIn('id', $retainedIds)
                ->where('is_enabled', true)->orderBy('sort_order')->get()->values();
            foreach ($enabledPhases as $index => $phase) {
                $settings = $phase->settings ?? [];
                $configuredPrevious = (int) data_get($settings, 'previous_phase_id', 0);
                $configuredNext = (int) data_get($settings, 'next_phase_id', 0);
                $configuredPreviousPhase = $enabledPhases->firstWhere('id', $configuredPrevious);
                $configuredNextPhase = $enabledPhases->firstWhere('id', $configuredNext);
                $settings['previous_phase_id'] = $configuredPreviousPhase
                    && (int) $configuredPreviousPhase->sort_order < (int) $phase->sort_order
                        ? $configuredPrevious
                        : $enabledPhases->get($index - 1)?->id;
                $settings['next_phase_id'] = $configuredNextPhase
                    && (int) $configuredNextPhase->sort_order > (int) $phase->sort_order
                        ? $configuredNext
                        : $enabledPhases->get($index + 1)?->id;
                $phase->update(['settings' => $settings]);
            }

            foreach ($existing->whereNotIn('id', $retainedIds) as $phase) {
                if ($phase->groups_count > 0 || $phase->matches_count > 0) {
                    $phase->update(['is_enabled' => false, 'is_public' => false]);
                } else {
                    $phase->delete();
                }
            }

            $this->syncApprovedEntries($competition);

            return $rows->where('is_enabled', true)->count();
        });
    }

    public function applyCompetitionTemplate(FiveQuillasCompetition $competition, string $format): int
    {
        return DB::transaction(function () use ($competition, $format): int {
            if ($competition->phases()->whereHas('groups')->exists()
                || $competition->phases()->whereHas('matches')->exists()) {
                throw ValidationException::withMessages([
                    'format' => 'No se puede reemplazar la plantilla porque ya existen zonas o partidos cargados.',
                ]);
            }
            $competition->phases()->delete();
            foreach ($this->phaseTemplate($competition->tournament, $format) as $phase) {
                $competition->phases()->create($phase);
            }
            $competition->update(['settings' => array_merge($competition->settings ?? [], ['format' => $format])]);
            $this->syncApprovedEntries($competition);
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
            // Los rangos Desde/Hasta se definen sobre bracket_order, que es el
            // mismo orden usado para separar las pestañas de cada turno. No se
            // debe volver a ordenar por los jugadores actuales: una corrección
            // de participantes cambiaría el conjunto y mezclaría mañana/tarde.
            $from = filled($schedule['from_match'] ?? null) ? (int) $schedule['from_match'] : 1;
            $to = filled($schedule['to_match'] ?? null) ? (int) $schedule['to_match'] : $matches->count();
            $matches = $matches->slice($from - 1, max(0, $to - $from + 1))->values();
            // Los pases libres ocupan un lugar en la grilla, pero no consumen mesa ni horario.
            if (! (bool) data_get($phase->settings, 'progressive_fill', false)) {
                $matches = $matches->filter(fn ($match): bool =>
                    ! $match->is_bye
                    && filled($match->player_one_entry_id)
                    && filled($match->player_two_entry_id)
                )->values();
            }
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
                    'table_number' => null,
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
            // Repara también competencias ya iniciadas con el criterio anterior,
            // que no reconocía las categorías temporales N y M del Ranking General.
            $this->refreshEntryPhaseAssignments($phase->competition);
            $phase->refresh()->loadMissing('competition.tournament.type');
            if ($phase->phase_type === 'zones') {
                throw ValidationException::withMessages(['phase' => 'Las zonas se confeccionan con la acción Cargar zonas.']);
            }
            $progressiveFill = (bool) data_get($phase->settings, 'progressive_fill', false);
            $preserveStartedMatches = $phase->matches()->where(function ($query): void {
                $query->where('status', 'in_progress')
                    ->orWhere(function ($completed): void {
                        $completed->where('status', 'completed')->where('is_bye', false);
                    });
            })->exists();
            if ($phase->matches()->exists()
                && $phase->phase_type !== 'slot_elimination'
                && ! $progressiveFill
                && ! $preserveStartedMatches) {
                // Esta acción se usa también después de corregir resultados de zonas.
                // La confirmación implica reconstruir la fase completa, incluidos los
                // resultados que pudiera contener la grilla anterior.
                $phaseEntryIds = $phase->matches()->get()
                    ->flatMap(fn ($match): array => [
                        $match->player_one_entry_id, $match->player_two_entry_id, $match->winner_entry_id,
                    ])->filter()->unique();
                $phase->competition->entries()->whereIn('id', $phaseEntryIds)->update([
                    'final_position' => null,
                    'status' => 'qualified',
                ]);
                $phase->matches()->delete();
            }

            $previous = $this->previousPhase($phase);

            if ($previous && $phase->phase_type !== 'slot_elimination'
                && ! $progressiveFill
                && $previous->status !== 'completed') {
                throw ValidationException::withMessages([
                    'phase' => "La fase {$previous->name} debe finalizar completamente antes de reunir sus clasificados en {$phase->name}.",
                ]);
            }

            $source = $previous
                ? $this->qualifiedEntriesFromPhase($previous)
                : $phase->competition->entries()->whereIn('status', ['eligible', 'assigned', 'qualified'])->get();
            $directEntries = $phase->competition->entries()->where('entry_phase_id', $phase->id)
                ->whereIn('status', ['eligible', 'assigned', 'qualified'])->get();
            $source = collect($source)->merge($directEntries)->unique('id')->values();

            if ($source->isEmpty() && ! ($progressiveFill && $phase->phase_type === 'bracket')) {
                throw ValidationException::withMessages(['phase' => 'La fase anterior todavía no tiene clasificados disponibles.']);
            }

            if ($phase->phase_type === 'slot_elimination') {
                return $this->createSlotElimination($phase, $previous);
            }

            if ($phase->phase_type === 'play_in') {
                return $this->createPlayIn($phase, $source);
            }

            if ($phase->phase_type === 'bracket') {
                $qualifiers = (int) data_get($phase->settings, 'qualifiers', 0);
                if ($qualifiers > 0
                    && $source->count() <= $qualifiers
                    && (! $progressiveFill || $previous?->status === 'completed')) {
                    $phase->update([
                        'status' => 'completed',
                        'settings' => array_merge($phase->settings ?? [], [
                            'qualified_entry_ids' => $source->pluck('id')->values()->all(),
                        ]),
                    ]);
                    $source->each(fn ($entry) => $entry->update(['status' => 'qualified']));

                    return 0;
                }
                $previousEntries = collect($source)
                    ->reject(fn ($entry): bool => (int) $entry->entry_phase_id === (int) $phase->id)
                    ->unique('id')->values();
                $directEntries = $directEntries->unique('id')->values();
                $configuredDirectCapacity = (int) data_get($phase->settings, 'direct_entry_seed_count', 0);
                $directCapacity = $directEntries->isNotEmpty()
                    ? ($configuredDirectCapacity > 0 ? $configuredDirectCapacity : $directEntries->count())
                    : 0;
                if ($directCapacity > 0 && $directEntries->count() > $directCapacity) {
                    throw ValidationException::withMessages([
                        'phase' => "Hay {$directEntries->count()} ingresos directos y solamente {$directCapacity} posiciones reservadas.",
                    ]);
                }
                // La configuración deportiva determina el cuadro, no la cantidad
                // encontrada durante una carga parcial. En el Circuito Argentino,
                // 32 ingresos directos y los 32 clasificados configurados forman
                // siempre un cuadro de 64, aunque todavía falte recuperar alguno.
                $expectedPrevious = $previous
                    ? (int) data_get($previous->settings, 'qualifiers', 0)
                    : 0;
                $lastRequiredSeed = $directCapacity > 0
                    ? $directCapacity + max($previousEntries->count(), $expectedPrevious)
                    : max($source->count(), $expectedPrevious);
                $requiredSize = $this->nextPowerOfTwo(max(2, $lastRequiredSeed));
                if ((int) data_get($phase->settings, 'bracket_size', 0) !== $requiredSize) {
                    $phase->update(['settings' => array_merge($phase->settings ?? [], [
                        'bracket_size' => $requiredSize,
                        'bracket_pairs' => $this->officialBracketPairs($requiredSize),
                    ])]);
                    $phase->refresh();
                }
            }

            if ($previous?->phase_type === 'zones'
                && in_array($phase->seeding_method, ['previous_results', 'manual'], true)) {
                $size = (int) data_get($phase->settings, 'bracket_size', 16);
                $seeded = [];
                $this->orderByPreviousPhaseResult($phase, $source)->take($size)
                    ->each(function ($entry, int $index) use (&$seeded): void {
                        $seed = $index + 1;
                        $entry->update(['seed' => $seed, 'status' => 'assigned']);
                        $seeded[$seed] = $entry;
                    });
            } else {
                $seeded = $this->seedBracketEntries($phase, $source);
            }
            if ($seeded === [] && $source->isNotEmpty()) {
                $size = (int) data_get($phase->settings, 'bracket_size', 16);
                $ordered = $previous?->phase_type === 'zones'
                    ? $this->orderByPreviousPhaseResult($phase, $source)
                    : collect($source)->values();
                $ordered->take($size)->each(function ($entry, int $index) use (&$seeded): void {
                    $seed = $index + 1;
                    $entry->update(['seed' => $seed, 'status' => 'assigned']);
                    $seeded[$seed] = $entry;
                });
            }
            if ($seeded === [] && ! $progressiveFill) {
                throw ValidationException::withMessages([
                    'phase' => 'No se encontraron jugadores clasificados para completar la grilla final.',
                ]);
            }
            if ($preserveStartedMatches) {
                // Los pases LIBRES automáticos no son resultados deportivos y se
                // pueden recalcular. Los partidos realmente iniciados permanecen
                // intactos; solamente se actualizan los cruces todavía pendientes.
                $phase->matches()->where('is_bye', true)->update([
                    'set_scores' => null,
                    'player_one_actual_sets' => 0,
                    'player_two_actual_sets' => 0,
                    'player_one_computed_sets' => 0,
                    'player_two_computed_sets' => 0,
                    'player_one_points' => 0,
                    'player_two_points' => 0,
                    'winner_entry_id' => null,
                    'is_bye' => false,
                    'status' => 'pending',
                ]);
                $count = $this->syncProgressiveBracket($phase, $seeded, true);
            } elseif ($progressiveFill && $phase->matches()->exists()) {
                $count = $this->syncProgressiveBracket($phase, $seeded, $previous?->status === 'completed');
            } else {
                $count = $this->createEliminationBracket(
                    $phase,
                    $seeded,
                    ! $progressiveFill || $previous?->status === 'completed',
                );
            }
            $phase->update([
                'status' => 'ready',
                'settings' => array_merge($phase->settings ?? [], ['bracket_build_version' => 3]),
            ]);

            return $count;
        });
    }

    public function drawCandidates(FiveQuillasPhase $phase)
    {
        $previous = $this->previousPhase($phase);
        $source = $previous
            ? $this->qualifiedEntriesFromPhase($previous)
            : $phase->competition->entries()->whereIn('status', ['eligible', 'assigned', 'qualified'])->get();
        $direct = $phase->competition->entries()->where('entry_phase_id', $phase->id)
            ->whereIn('status', ['eligible', 'assigned', 'qualified'])->get();

        return collect($source)->merge($direct)->unique('id')->each->loadMissing('player')->values();
    }

    public function bracketParticipantRows(FiveQuillasPhase $phase): array
    {
        $phase->loadMissing('competition');
        $this->refreshEntryPhaseAssignments($phase->competition);
        $phase->refresh()->loadMissing('competition');
        $previous = $this->previousPhase($phase);
        $qualified = $previous
            ? $this->qualifiedEntriesFromPhase($previous)
            : $phase->competition->entries()->whereIn('status', ['eligible', 'assigned', 'qualified'])
                ->with('player')->get();
        $direct = $phase->competition->entries()->where('entry_phase_id', $phase->id)
            ->whereIn('status', ['eligible', 'assigned', 'qualified'])
            ->with('player')->get()->unique('id')->values();
        $qualified = collect($qualified)->reject(fn ($entry): bool => $direct->contains('id', $entry->id))
            ->each->loadMissing('player')->unique('id')->values();
        $configuredDirectCapacity = (int) data_get($phase->settings, 'direct_entry_seed_count', 0);
        $directCapacity = $direct->isNotEmpty()
            ? ($configuredDirectCapacity > 0 ? $configuredDirectCapacity : $direct->count())
            : 0;
        $overrides = collect(data_get($phase->settings, 'participant_seed_overrides', []))
            ->mapWithKeys(fn ($seed, $entryId): array => [(int) $entryId => (int) $seed]);
        $rank = fn ($entry): int => $entry->national_ranking_snapshot ?: PHP_INT_MAX;
        $direct = $direct->sortBy($rank)->values();
        // El bloque reservado es estricto. Si el horario NACIONALES o MASTER
        // contiene más inscriptos que lugares directos, los de peor ranking pasan
        // al bloque de emparejamiento/reclasificación. Nunca pueden invadir la
        // primera posición reservada para los clasificados, que antes provocaba
        // que 61 registros terminaran mostrando solamente 60 jugadores.
        $directOverflow = $directCapacity > 0 ? $direct->slice($directCapacity) : collect();
        $direct = $directCapacity > 0 ? $direct->take($directCapacity)->values() : $direct;
        $qualified = $qualified->concat($directOverflow)->unique('id')->sortBy($rank)->values();

        // Descarta posiciones heredadas de cuadros anteriores (por ejemplo 128)
        // y cualquier duplicado. Así todos los jugadores aparecen una sola vez en
        // la grilla vigente y el formulario puede guardarse sin falsas diferencias.
        $usedSeeds = collect();
        $assignSeeds = function ($entries, int $start, int $end) use ($overrides, $usedSeeds): array {
            $next = $start;
            return $entries->mapWithKeys(function ($entry) use ($overrides, $usedSeeds, &$next, $start, $end): array {
                $requested = (int) $overrides->get((int) $entry->id, 0);
                $seed = $requested >= $start && $requested <= $end && ! $usedSeeds->contains($requested)
                    ? $requested
                    : 0;
                while ($seed === 0 && $next <= $end) {
                    if (! $usedSeeds->contains($next)) { $seed = $next; }
                    $next++;
                }
                $usedSeeds->push($seed);
                return [(int) $entry->id => $seed];
            })->all();
        };
        $directSeeds = $assignSeeds($direct, 1, max(1, $directCapacity));
        $qualifiedStart = $directCapacity + 1;
        $qualifiedSeeds = $assignSeeds($qualified, $qualifiedStart, $directCapacity + $qualified->count());
        $expectedQualified = $previous
            ? (int) data_get($previous->settings, 'qualifiers', 0)
            : 0;
        $requiredSize = $this->nextPowerOfTwo(max(
            2,
            $directCapacity + max($qualified->count(), $expectedQualified),
        ));
        if ((int) data_get($phase->settings, 'bracket_size', 0) !== $requiredSize) {
            $phase->update(['settings' => array_merge($phase->settings ?? [], [
                'bracket_size' => $requiredSize,
                'bracket_pairs' => $this->officialBracketPairs($requiredSize),
                'participant_seed_overrides' => [],
            ])]);
        }

        return $direct->map(fn ($entry, int $index): array => [
            'entry_id' => (int) $entry->id,
            'player_name' => (string) ($entry->player?->full_name ?? 'Jugador sin nombre'),
            'origin' => 'Ingreso directo en '.$phase->name,
            'ranking' => $entry->national_ranking_snapshot,
            'seed_position' => $directSeeds[(int) $entry->id],
            'origin_type' => 'direct',
        ])->concat($qualified->map(fn ($entry, int $index): array => [
            'entry_id' => (int) $entry->id,
            'player_name' => (string) ($entry->player?->full_name ?? 'Jugador sin nombre'),
            'origin' => $directOverflow->contains('id', $entry->id)
                ? 'Emparejamiento / reclasificación por ranking'
                : 'Clasificado de '.($previous?->name ?? 'la fase anterior'),
            'ranking' => $entry->national_ranking_snapshot,
            'seed_position' => $qualifiedSeeds[(int) $entry->id],
            'origin_type' => 'qualified',
        ]))->sortBy('seed_position')->values()->all();
    }

    public function bracketParticipantDisplayRows(FiveQuillasPhase $phase): array
    {
        if ($phase->matches()->exists()) {
            $entryIds = $phase->matches()->where('round_number', 1)->get()
                ->flatMap(fn ($match): array => [
                    $match->player_one_entry_id,
                    $match->player_two_entry_id,
                ])->filter()->map(fn ($id): int => (int) $id)->unique();
            $previous = $this->previousPhase($phase);

            return $phase->competition->entries()->with('player')->whereIn('id', $entryIds)->get()
                ->sortBy(fn ($entry): array => [
                    $entry->seed ?: PHP_INT_MAX,
                    $entry->national_ranking_snapshot ?: PHP_INT_MAX,
                ])->values()->map(fn ($entry): array => [
                    'entry_id' => (int) $entry->id,
                    'player_name' => (string) ($entry->player?->full_name ?? 'Jugador sin nombre'),
                    'origin' => (int) $entry->entry_phase_id === (int) $phase->id
                        ? 'Ingreso directo en '.$phase->name
                        : 'Clasificado de '.($previous?->name ?? 'la fase anterior'),
                    'ranking' => $entry->national_ranking_snapshot,
                    'seed_position' => $entry->seed,
                ])->all();
        }

        return $this->bracketParticipantRows($phase);
    }

    public function qualifiedParticipantRows(FiveQuillasPhase $phase): array
    {
        $nextPhaseId = (int) data_get($phase->settings, 'next_phase_id', 0);
        $nextPhase = $nextPhaseId > 0
            ? $phase->competition->phases()->where('is_enabled', true)->find($nextPhaseId)
            : $phase->competition->phases()->where('is_enabled', true)
                ->where('sort_order', '>', $phase->sort_order)->orderBy('sort_order')->first();
        if (! $nextPhase) {
            return [];
        }

        if ($phase->phase_type === 'zones') {
            // La vista histórica usa exclusivamente las posiciones y estadísticas
            // persistidas al disputar la competencia. Consultarla no recalcula ni
            // modifica la clasificación con datos actuales.
            $entries = $phase->groups()->with('groupEntries.entry.player')->orderBy('id')->get()
                ->flatMap->groupEntries->whereIn('final_place', [1, 2])
                ->sort(function ($left, $right): int {
                    foreach ([
                        [(int) $left->final_place, (int) $right->final_place, 'asc'],
                        [(int) $left->sets_difference, (int) $right->sets_difference, 'desc'],
                        [(float) $left->points_average, (float) $right->points_average, 'desc'],
                    ] as [$a, $b, $direction]) {
                        if ($a !== $b) { return $direction === 'asc' ? $a <=> $b : $b <=> $a; }
                    }

                    return (int) $left->entry_id <=> (int) $right->entry_id;
                })->pluck('entry')->filter()->unique('id')->values();
        } else {
            $entries = collect($this->qualifiedEntriesFromPhase($phase))->unique('id');
            $winnerMatches = $phase->matches()->where('status', 'completed')->whereNotNull('winner_entry_id');
            if ($phase->phase_type !== 'slot_elimination') {
                $winnerMatches->where('round_number', (int) $phase->matches()->max('round_number'));
            }
            $historicalOrder = $winnerMatches->orderBy('round_number')->orderBy('bracket_order')
                ->pluck('winner_entry_id')->map(fn ($id): int => (int) $id)->unique()->values();
            $matches = $phase->matches()->where('status', 'completed')->get();
            $entries = $entries->sort(function ($left, $right) use ($phase, $historicalOrder, $matches): int {
                $leftHistorical = $historicalOrder->search((int) $left->id);
                $rightHistorical = $historicalOrder->search((int) $right->id);
                $leftHistorical = $leftHistorical === false ? PHP_INT_MAX : (int) $leftHistorical;
                $rightHistorical = $rightHistorical === false ? PHP_INT_MAX : (int) $rightHistorical;

                // La lista pertenece a esta fase y debe respetar su propio método
                // de clasificación. Ranking y sorteo son valores históricos
                // persistidos; consultar la lista nunca los recalcula con datos
                // vigentes al momento de visualizarla.
                $comparison = match ($phase->seeding_method) {
                    'national_ranking' => ($left->national_ranking_snapshot ?: PHP_INT_MAX)
                        <=> ($right->national_ranking_snapshot ?: PHP_INT_MAX),
                    'number_draw', 'automatic_draw' => ($left->seed ?: PHP_INT_MAX)
                        <=> ($right->seed ?: PHP_INT_MAX),
                    'previous_results' => $this->compareQualifiedPhasePerformance(
                        $matches,
                        (int) $left->id,
                        (int) $right->id,
                        $leftHistorical,
                        $rightHistorical,
                    ),
                    default => $leftHistorical <=> $rightHistorical,
                };

                if ($comparison !== 0) {
                    return $comparison;
                }

                return [
                    $left->national_ranking_snapshot ?: PHP_INT_MAX,
                    $left->player?->last_name ?? '',
                    (int) $left->id,
                ] <=> [
                    $right->national_ranking_snapshot ?: PHP_INT_MAX,
                    $right->player?->last_name ?? '',
                    (int) $right->id,
                ];
            })->values();
        }

        return $entries->values()->map(function ($entry, int $index) use ($phase, $nextPhase): array {
            $entry->loadMissing('player.club');

            return [
                'position' => $index + 1,
                'player' => (string) ($entry->player?->full_name ?? 'Jugador sin nombre'),
                'club' => (string) ($entry->player?->club?->short_name ?: $entry->player?->club?->name),
                'ranking' => $entry->national_ranking_snapshot,
                'origin' => 'Clasificado de '.$phase->name,
                'next_phase' => $nextPhase->name,
            ];
        })->all();
    }

    private function compareQualifiedPhasePerformance(
        $matches,
        int $leftEntryId,
        int $rightEntryId,
        int $leftHistorical,
        int $rightHistorical,
    ): int {
        $leftStats = $this->entryPerformance($matches, $leftEntryId);
        $rightStats = $this->entryPerformance($matches, $rightEntryId);
        foreach (['sets_difference', 'average'] as $field) {
            if ($leftStats[$field] !== $rightStats[$field]) {
                return $rightStats[$field] <=> $leftStats[$field];
            }
        }

        if ($leftHistorical !== $rightHistorical) {
            return $leftHistorical <=> $rightHistorical;
        }

        return $leftEntryId <=> $rightEntryId;
    }

    public function bracketMatchupRows(FiveQuillasPhase $phase): array
    {
        $participants = collect($this->bracketParticipantRows($phase));
        $bySeed = $participants->keyBy('seed_position');
        $configuredCapacity = (int) data_get($phase->settings, 'direct_entry_seed_count', 0);
        $directCapacity = $participants->where('origin_type', 'direct')->isNotEmpty()
            ? max($configuredCapacity, $participants->where('origin_type', 'direct')->count())
            : 0;
        $previous = $this->previousPhase($phase);
        $expectedQualified = $previous
            ? (int) data_get($previous->settings, 'qualifiers', 0)
            : 0;
        $size = $this->nextPowerOfTwo(max(
            2,
            $directCapacity + max($participants->where('origin_type', 'qualified')->count(), $expectedQualified),
        ));

        return collect($this->officialBracketPairs($size))->map(function (array $pair, int $index) use ($bySeed): array {
            $one = $bySeed->get($pair[0]);
            $two = $bySeed->get($pair[1]);

            return [
                'player_one_seed' => $pair[0],
                'player_one_entry_id' => $one['entry_id'] ?? null,
                'player_two_seed' => $pair[1],
                'player_two_entry_id' => $two['entry_id'] ?? null,
            ];
        })->all();
    }

    public function saveBracketParticipantOrder(FiveQuillasPhase $phase, array $rows): void
    {
        $expected = collect($this->bracketParticipantRows($phase))->keyBy('entry_id');
        $submitted = collect($rows)->keyBy(fn (array $row): int => (int) ($row['entry_id'] ?? 0));
        if ($submitted->keys()->sort()->values()->all() !== $expected->keys()->sort()->values()->all()) {
            throw ValidationException::withMessages([
                'participants' => 'La lista cambió mientras estaba abierta. Volvé a abrirla para revisar todos los participantes.',
            ]);
        }
        $directCount = $expected->where('origin_type', 'direct')->count();
        $configuredDirectCapacity = (int) data_get($phase->settings, 'direct_entry_seed_count', 0);
        $directCapacity = $directCount > 0
            ? ($configuredDirectCapacity > 0 ? $configuredDirectCapacity : $directCount)
            : 0;
        if ($directCount > $directCapacity) {
            throw ValidationException::withMessages([
                'participants' => "Hay {$directCount} ingresos directos y solamente {$directCapacity} posiciones reservadas.",
            ]);
        }
        $seeds = $submitted->map(fn (array $row): int => (int) ($row['seed_position'] ?? 0));
        if ($seeds->contains(fn (int $seed): bool => $seed < 1) || $seeds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['participants' => 'Cada participante debe tener una posición diferente y mayor que cero.']);
        }
        $qualifiedCount = $expected->where('origin_type', 'qualified')->count();
        $maximumSeed = $this->nextPowerOfTwo(max(2, $directCapacity + $qualifiedCount));
        if ($seeds->contains(fn (int $seed): bool => $seed > $maximumSeed)) {
            throw ValidationException::withMessages([
                'participants' => "Las posiciones de esta fase deben estar comprendidas entre 1 y {$maximumSeed}.",
            ]);
        }
        foreach ($submitted as $entryId => $row) {
            $expectedRow = $expected->get((int) $entryId);
            $seed = (int) $row['seed_position'];
            if ($expectedRow['origin_type'] === 'direct' && $seed > $directCapacity) {
                throw ValidationException::withMessages(['participants' => "Los ingresos directos deben permanecer entre las posiciones 1 y {$directCapacity}."]);
            }
            if ($expectedRow['origin_type'] === 'qualified' && $seed <= $directCapacity) {
                throw ValidationException::withMessages(['participants' => 'Los clasificados de la fase anterior deben ubicarse después de las posiciones reservadas para ingreso directo.']);
            }
        }
        $phase->update(['settings' => array_merge($phase->settings ?? [], [
            'participant_seed_overrides' => $submitted->mapWithKeys(
                fn (array $row, $entryId): array => [(int) $entryId => (int) $row['seed_position']]
            )->all(),
        ])]);
    }

    public function saveDrawNumbers(FiveQuillasPhase $phase, array $rows): void
    {
        $size = (int) data_get($phase->settings, 'bracket_size', 16);
        $candidates = $this->drawCandidates($phase)->keyBy('id');
        $numbers = collect($rows)->pluck('draw_number')->filter()->map(fn ($number): int => (int) $number);
        if ($numbers->count() !== $candidates->count() || $numbers->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['draws' => 'Cada clasificado debe tener un número de sorteo diferente.']);
        }
        if ($numbers->contains(fn (int $number): bool => $number < 1 || $number > $size)) {
            throw ValidationException::withMessages(['draws' => "Los números deben estar comprendidos entre 1 y {$size}."]);
        }
        foreach ($rows as $row) {
            $entry = $candidates->get((int) ($row['entry_id'] ?? 0));
            if ($entry) { $entry->update(['seed' => (int) $row['draw_number']]); }
        }
        if ((bool) data_get($phase->settings, 'progressive_fill', false)) {
            $this->preparePhase($phase->fresh());
        }
    }

    public function assignSelectedScoringRule(FiveQuillasEntry $entry, string $ruleKey): void
    {
        $entry->loadMissing(['competition.tournament.type', 'registration']);
        if (! $entry->registration) {
            throw ValidationException::withMessages(['scoring_rule' => 'El jugador no tiene una inscripción vinculada.']);
        }
        $scoring = app(TournamentScoringService::class);
        if (str_starts_with($ruleKey, 'instance:')) {
            $scoring->assignByTournamentInstance($entry->registration, (int) Str::after($ruleKey, 'instance:'));
            return;
        }
        if (str_starts_with($ruleKey, 'code:')) {
            $scoring->assignByCode($entry->registration, Str::after($ruleKey, 'code:'));
            return;
        }
        throw ValidationException::withMessages(['scoring_rule' => 'Seleccioná una puntuación válida.']);
    }

    public function proposedPointsForPlacement(Tournament $tournament, int $place): ?float
    {
        $rule = $this->ruleForPlacement(
            app(TournamentScoringService::class)->getRules($tournament),
            $place,
        );

        return $rule && array_key_exists('points', $rule) ? (float) $rule['points'] : null;
    }

    public function proposedPointsForEntry(Tournament $tournament, FiveQuillasCompetition $competition, FiveQuillasEntry $entry): ?float
    {
        $rule = $this->ruleForEntryPlacement(
            app(TournamentScoringService::class)->getRules($tournament),
            $competition,
            $entry,
        );

        return $rule && array_key_exists('points', $rule) ? (float) $rule['points'] : null;
    }

    public function commitFinalScoring(FiveQuillasCompetition $competition): array
    {
        $competition->loadMissing('tournament.type');
        $tournament = $competition->tournament;
        $scoring = app(TournamentScoringService::class);
        $rules = $scoring->getRules($tournament);

        if ($competition->status !== 'completed'
            || $competition->phases()->where('is_enabled', true)->where('status', '!=', 'completed')->exists()) {
            throw ValidationException::withMessages([
                'scoring' => 'Finalizá todas las fases antes de guardar la puntuación definitiva.',
            ]);
        }

        if (! $tournament->type?->assigns_points || $tournament->type?->scoring_method !== 'position') {
            throw ValidationException::withMessages([
                'scoring' => 'El tipo de torneo no está configurado para asignar puntuación por posición.',
            ]);
        }
        if ($rules === []) {
            throw ValidationException::withMessages([
                'scoring' => 'El tipo de torneo no tiene una tabla de puntuación configurada.',
            ]);
        }

        $summary = DB::transaction(function () use ($competition, $tournament, $scoring, $rules): array {
            $entries = $competition->entries()->whereNotNull('final_position')
                ->orderBy('final_position')->get()
                ->unique('tournament_registration_id')->values();
            $registrationIds = $entries->pluck('tournament_registration_id')->filter()->values();
            $registrations = TournamentRegistration::query()->where('tournament_id', $tournament->id)
                ->whereKey($registrationIds)->lockForUpdate()->get()->keyBy('id');
            $result = ['assigned' => 0, 'existing' => 0, 'without_rule' => 0, 'without_registration' => 0];

            foreach ($entries as $entry) {
                $registration = $registrations->get((int) $entry->tournament_registration_id);
                if (! $registration) {
                    $result['without_registration']++;
                    continue;
                }
                if ($registration->points !== null
                    || filled($registration->result_code)
                    || filled($registration->tournament_instance_id)) {
                    $result['existing']++;
                    continue;
                }

                $rule = $this->ruleForEntryPlacement($rules, $competition, $entry);
                if (! $rule) {
                    $result['without_rule']++;
                    continue;
                }

                if ($scoring->usesOfficialInstances($tournament) && ! empty($rule['tournament_instance_id'])) {
                    $scoring->assignByTournamentInstance($registration, (int) $rule['tournament_instance_id']);
                } elseif (! empty($rule['code'])) {
                    $scoring->assignByCode($registration, (string) $rule['code']);
                } else {
                    $result['without_rule']++;
                    continue;
                }
                $result['assigned']++;
            }

            $competition->update(['settings' => array_merge($competition->settings ?? [], [
                'scoring_confirmed_at' => now()->toIso8601String(),
                'scoring_confirmed_by' => auth()->id(),
            ])]);

            return $result;
        });

        if ($summary['assigned'] > 0 && $tournament->type?->affects_ranking) {
            RankingService::syncGeneralRanking();
        }

        return $summary;
    }

    private function createSlotElimination(FiveQuillasPhase $phase, ?FiveQuillasPhase $previous): int
    {
        if (! $previous || $previous->phase_type !== 'zones') {
            throw ValidationException::withMessages(['phase' => 'La eliminación por horario requiere una fase de zonas anterior.']);
        }

        $configuredCrossings = data_get($phase->settings, 'slot_crossings', []);
        if (is_array($configuredCrossings) && $configuredCrossings !== []) {
            return $this->createManualSlotElimination($phase, $configuredCrossings);
        }

        $groups = $previous->groups()->with(['groupEntries.entry'])->orderBy('tournament_slot_id')->orderBy('sort_order')->get()
            ->groupBy('tournament_slot_id');
        $order = 0;
        $availableMatches = 0;
        foreach ($groups as $slotGroups) {
            $slotGroups = $slotGroups->values();
            for ($index = 0; $index < $slotGroups->count(); $index += 2) {
                $left = $slotGroups->get($index);
                $right = $slotGroups->get($index + 1);
                if (! $right) { continue; }
                $leftRows = $left->groupEntries->keyBy('final_place');
                $rightRows = $right->groupEntries->keyBy('final_place');
                foreach ([[$leftRows->get(1), $rightRows->get(2)], [$rightRows->get(1), $leftRows->get(2)]] as $pair) {
                    $order++;
                    if (! $pair[0] || ! $pair[1]) { continue; }
                    $availableMatches++;
                    $match = $phase->matches()->where('round_number', 1)
                        ->where('bracket_order', $order)->first();
                    $attributes = [
                        'round_number' => 1, 'bracket_order' => $order,
                        'label' => 'Cruce por horario '.$order,
                        'player_one_entry_id' => $pair[0]->entry_id,
                        'player_two_entry_id' => $pair[1]->entry_id,
                        'is_public' => (bool) $phase->is_public,
                    ];
                    if (! $match) {
                        $phase->matches()->create($attributes);
                    } elseif ($match->status !== 'completed') {
                        $match->update($attributes);
                    }
                }
            }
        }
        if ($availableMatches === 0) {
            throw ValidationException::withMessages(['phase' => 'Finalizá y clasificá las zonas antes de crear los cruces.']);
        }
        $phase->update([
            'status' => $phase->matches()->whereIn('status', ['in_progress', 'completed'])->exists()
                ? 'in_progress'
                : 'ready',
        ]);
        return $availableMatches;
    }

    public function slotEliminationCandidates(FiveQuillasPhase $phase)
    {
        $previous = $this->previousPhase($phase);
        if (! $previous || $previous->phase_type !== 'zones') {
            return collect();
        }

        return $this->qualifiedZoneRows($previous)
            ->each->loadMissing(['entry.player.club', 'group.slot'])
            ->filter(fn ($row): bool => (bool) $row->entry && (bool) $row->group?->tournament_slot_id)
            ->values();
    }

    public function previousPhaseFor(FiveQuillasPhase $phase): ?FiveQuillasPhase
    {
        return $this->previousPhase($phase);
    }

    public function createManualSlotElimination(FiveQuillasPhase $phase, array $crossings): int
    {
        return DB::transaction(function () use ($phase, $crossings): int {
            if ($phase->phase_type !== 'slot_elimination') {
                throw ValidationException::withMessages(['crossings' => 'La fase seleccionada no corresponde a cruces por horario.']);
            }
            $previous = $this->previousPhase($phase);
            if (! $previous || $previous->phase_type !== 'zones') {
                throw ValidationException::withMessages(['crossings' => 'La fase anterior debe ser una fase de zonas.']);
            }
            $groups = $previous->groups()->with('groupEntries')->get()->keyBy('id');
            $rows = collect($crossings)->map(fn (array $row): array => [
                'match_id' => (int) ($row['match_id'] ?? 0),
                'slot_id' => (int) ($row['slot_id'] ?? 0),
                'first_group_id' => (int) ($row['first_group_id'] ?? 0),
                'second_group_id' => (int) ($row['second_group_id'] ?? 0),
            ])->filter(fn (array $row): bool => $row['first_group_id'] > 0 || $row['second_group_id'] > 0)->values();

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['crossings' => 'Agregá al menos un enfrentamiento.']);
            }
            $usedFirstGroups = collect();
            $usedSecondGroups = collect();
            foreach ($rows as $index => $row) {
                $firstGroup = $groups->get($row['first_group_id']);
                $secondGroup = $groups->get($row['second_group_id']);
                if (! $firstGroup || ! $secondGroup) {
                    throw ValidationException::withMessages(['crossings' => 'El cruce '.($index + 1).' debe indicar dos zonas válidas.']);
                }
                if ((int) $firstGroup->tournament_slot_id !== $row['slot_id']
                    || (int) $secondGroup->tournament_slot_id !== $row['slot_id']) {
                    throw ValidationException::withMessages(['crossings' => 'Las dos zonas del cruce '.($index + 1).' deben pertenecer al mismo horario.']);
                }
                if ((int) $firstGroup->id === (int) $secondGroup->id) {
                    throw ValidationException::withMessages(['crossings' => 'El 1.º y el 2.º del cruce '.($index + 1).' deben provenir de zonas distintas.']);
                }
                $usedFirstGroups->push((int) $firstGroup->id);
                $usedSecondGroups->push((int) $secondGroup->id);
            }
            if ($usedFirstGroups->duplicates()->isNotEmpty() || $usedSecondGroups->duplicates()->isNotEmpty()) {
                throw ValidationException::withMessages(['crossings' => 'Cada puesto clasificado de una zona puede utilizarse una sola vez.']);
            }

            $existingMatches = $phase->matches()->get()->keyBy('id');
            $matchesByOrder = $existingMatches->keyBy(fn ($match): int => (int) $match->bracket_order);
            $rows = $rows->map(function (array $row, int $index) use ($matchesByOrder): array {
                if ($row['match_id'] < 1 && $matchesByOrder->has($index + 1)) {
                    $row['match_id'] = (int) $matchesByOrder->get($index + 1)->id;
                }

                return $row;
            });
            $submittedMatchIds = $rows->pluck('match_id')->filter()->unique();
            $removedLockedMatch = $existingMatches->first(fn ($match): bool =>
                in_array($match->status, ['in_progress', 'completed'], true)
                && ! $submittedMatchIds->contains((int) $match->id)
            );
            if ($removedLockedMatch) {
                throw ValidationException::withMessages(['crossings' => 'No se puede eliminar un cruce que ya tiene el partido iniciado o finalizado.']);
            }

            $keptMatchIds = collect();
            $settingsRows = collect();
            foreach ($rows as $index => $row) {
                $firstGroup = $groups->get($row['first_group_id']);
                $secondGroup = $groups->get($row['second_group_id']);
                $crossing = [
                    'slot_id' => $row['slot_id'],
                    'first_group_id' => $row['first_group_id'],
                    'second_group_id' => $row['second_group_id'],
                ];
                $attributes = [
                    'round_number' => 1,
                    'bracket_order' => $index + 1,
                    'label' => 'Cruce por horario '.($index + 1),
                    'player_one_entry_id' => $firstGroup->groupEntries->firstWhere('final_place', 1)?->entry_id,
                    'player_two_entry_id' => $secondGroup->groupEntries->firstWhere('final_place', 2)?->entry_id,
                    'set_scores' => ['slot_crossing' => $crossing],
                    'is_public' => (bool) $phase->is_public,
                ];
                $match = $row['match_id'] > 0 ? $existingMatches->get($row['match_id']) : null;
                if ($row['match_id'] > 0 && ! $match) {
                    throw ValidationException::withMessages(['crossings' => 'Uno de los cruces indicados ya no pertenece a esta fase.']);
                }
                if ($match && in_array($match->status, ['in_progress', 'completed'], true)) {
                    $stored = data_get($match->set_scores, 'slot_crossing');
                    if (is_array($stored) && (
                        (int) ($stored['slot_id'] ?? 0) !== $crossing['slot_id']
                        || (int) ($stored['first_group_id'] ?? 0) !== $crossing['first_group_id']
                        || (int) ($stored['second_group_id'] ?? 0) !== $crossing['second_group_id']
                    )) {
                        throw ValidationException::withMessages(['crossings' => 'No se puede cambiar un cruce cuyo partido ya comenzó.']);
                    }
                } elseif ($match) {
                    $match->update($attributes);
                } else {
                    $match = $phase->matches()->create($attributes);
                }
                $keptMatchIds->push((int) $match->id);
                $settingsRows->push($crossing);
            }

            $phase->matches()->whereNotIn('id', $keptMatchIds->all())
                ->where('status', 'pending')->delete();
            $phase->update([
                'status' => $phase->matches()->whereIn('status', ['in_progress', 'completed'])->exists()
                    ? 'in_progress'
                    : 'ready',
                'settings' => array_merge($phase->settings ?? [], ['slot_crossings' => $settingsRows->all()]),
            ]);

            return $rows->count();
        });
    }

    private function scoresWithCrossingMetadata(FiveQuillasMatch $match, array $scores): array
    {
        $crossing = data_get($match->set_scores, 'slot_crossing');

        return is_array($crossing)
            ? ['slot_crossing' => $crossing, ...$scores]
            : $scores;
    }

    private function nextPowerOfTwo(int $value): int
    {
        $size = 2;
        while ($size < $value) {
            $size *= 2;
        }

        return $size;
    }

    private function entryPhaseForRegistration($phases, ?Player $player, ?string $slotName): ?FiveQuillasPhase
    {
        $categoryIds = $phases->flatMap(fn ($phase) => data_get($phase->settings, 'entry_category_ids', []))
            ->filter()->map(fn ($id): int => (int) $id)->unique();
        $categoryCodes = Category::query()->whereIn('id', $categoryIds)
            ->pluck('code', 'id')->map(fn ($code): string => Str::upper(trim((string) $code)));
        $phaseCodes = $phases->mapWithKeys(function ($phase) use ($categoryCodes): array {
            $codes = collect(data_get($phase->settings, 'entry_category_ids', []))
                ->map(fn ($id): ?string => $categoryCodes->get((int) $id))->filter()->unique()->values();

            return [(int) $phase->id => $codes];
        });

        $normalizedSlot = Str::lower(Str::ascii((string) $slotName));
        $specialCode = Str::contains($normalizedSlot, ['master'])
            ? 'M'
            : (Str::contains($normalizedSlot, ['nacional', '1ra', 'primera']) ? 'N' : null);
        // En torneos históricos la categoría del Ranking General actual puede ser
        // distinta de la que tenía el jugador al inscribirse. El horario NACIONALES
        // o MASTER es la fuente persistente que define el ingreso directo; el ranking
        // se utiliza después únicamente para ordenar ese bloque.
        if ($specialCode) {
            $slotPhase = $phases->first(function ($phase) use ($phaseCodes, $specialCode): bool {
                if ($phaseCodes->get((int) $phase->id, collect())->contains($specialCode)) {
                    return true;
                }
                $label = Str::lower(Str::ascii($phase->code.' '.$phase->name));

                return $specialCode === 'M'
                    ? Str::contains($label, 'master')
                    : Str::contains($label, ['nacional', '1ra', 'primera']);
            });
            if ($slotPhase) {
                return $slotPhase;
            }
        }

        return $phases->first(fn ($phase): bool => collect(data_get($phase->settings, 'entry_category_ids', []))
            ->map(fn ($id): int => (int) $id)->contains((int) $player?->category_id));
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

        $configuredDirectCount = (int) data_get($phase->settings, 'direct_entry_seed_count', 0);
        $direct = $configuredDirectCount > 0
            ? $source->where('entry_phase_id', $phase->id)
                ->sortBy(fn ($entry): int => $entry->national_ranking_snapshot ?: PHP_INT_MAX)
                ->values()
            : collect();

        if ($direct->isNotEmpty()) {
            // Usa el mismo plan que se presenta en el modal. De este modo el
            // contador, las posiciones editadas y los partidos de la grilla nunca
            // vuelven a calcular grupos distintos.
            $sourceById = $source->keyBy('id');
            collect($this->bracketParticipantRows($phase))->each(function (array $row) use (&$seeds, $sourceById, $size): void {
                $seed = (int) $row['seed_position'];
                $entry = $sourceById->get((int) $row['entry_id']);
                if ($entry && $seed >= 1 && $seed <= $size) {
                    $seeds[$seed] = $entry;
                }
            });
        } else {
            if ($phase->seeding_method === 'number_draw') {
                $missing = $source->filter(fn ($entry): bool => ! $entry->seed || $entry->seed > $size);
                if ($missing->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'draw_numbers' => 'Asigná un número de sorteo válido a cada clasificado antes de preparar la grilla.',
                    ]);
                }
                if ($source->pluck('seed')->duplicates()->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'draw_numbers' => 'Los números de sorteo no pueden repetirse.',
                    ]);
                }
                $source->each(fn ($entry) => $seeds[(int) $entry->seed] = $entry);
            } else {
                $ordered = match ($phase->seeding_method) {
                    'national_ranking' => $source->sortBy(fn ($entry): int => $entry->national_ranking_snapshot ?: PHP_INT_MAX)->values(),
                    'previous_results' => $this->orderByPreviousPhaseResult($phase, $source),
                    default => $source->values(),
                };
                $ordered->take($size)->each(fn ($entry, $index) => $seeds[$index + 1] = $entry);
            }
        }

        foreach ($seeds as $seed => $entry) {
            $entry->update(['seed' => $seed, 'status' => 'assigned']);
        }
        return $seeds;
    }

    private function createEliminationBracket(FiveQuillasPhase $phase, array $seeds, bool $finalizeByes = true): int
    {
        $size = (int) data_get($phase->settings, 'bracket_size', 16);
        $rounds = (int) log($size, 2);
        // La cantidad configurada de clasificados determina exactamente cuántas
        // rondas se crean. Un cuadro de 64 que entrega 16 jugadores contiene dos
        // rondas: 32 partidos y luego 16 partidos.
        $qualifiers = (int) data_get($phase->settings, 'qualifiers', 0);
        $roundsForQualifiers = $qualifiers > 0 && $qualifiers < $size
            ? max(1, (int) ceil(log($size / $qualifiers, 2)))
            : $rounds;
        $roundsToCreate = $roundsForQualifiers;
        $created = [];
        for ($round = $roundsToCreate; $round >= 1; $round--) {
            $count = intdiv($size, 2 ** $round);
            for ($order = 1; $order <= $count; $order++) {
                $next = $created[$round + 1][(int) ceil($order / 2)] ?? null;
                $created[$round][$order] = $phase->matches()->create([
                    'round_number' => $round,
                    'bracket_order' => $order,
                    'label' => $this->eliminationRoundLabel($size, $round).' '.$order,
                    'next_match_id' => $next?->id,
                    'next_match_position' => $next ? ($order % 2 === 1 ? 1 : 2) : null,
                    'is_public' => (bool) $phase->is_public,
                ]);
            }
        }
        $pairs = collect(data_get($phase->settings, 'bracket_pairs', []))->values()->all();
        if (count($pairs) !== intdiv($size, 2)) {
            $pairs = $this->officialBracketPairs($size);
            $phase->update(['settings' => array_merge($phase->settings ?? [], ['bracket_pairs' => $pairs])]);
        }
        foreach ($pairs as $index => $pair) {
            $match = $created[1][$index + 1];
            $match->update([
                'player_one_entry_id' => ($seeds[$pair[0]] ?? null)?->id,
                'player_two_entry_id' => ($seeds[$pair[1]] ?? null)?->id,
            ]);
            if ($finalizeByes) {
                $this->resolveBye($match->fresh());
            }
        }
        $assignedSlots = $phase->matches()->where('round_number', 1)
            ->get(['player_one_entry_id', 'player_two_entry_id'])
            ->flatMap(fn ($match): array => [$match->player_one_entry_id, $match->player_two_entry_id])
            ->filter()->unique()->count();
        $expectedAssignments = min(count($seeds), $size);
        if ($assignedSlots !== $expectedAssignments) {
            throw ValidationException::withMessages([
                'phase' => "La grilla esperaba {$expectedAssignments} clasificados y pudo ubicar {$assignedSlots}. Revisá la configuración del cuadro.",
            ]);
        }
        if ($finalizeByes) {
            $this->resolveReadyByes($phase->matches()->pluck('id'));
        }
        return $phase->matches()->count();
    }

    private function syncProgressiveBracket(FiveQuillasPhase $phase, array $seeds, bool $finalizeByes): int
    {
        $pairs = collect(data_get($phase->settings, 'bracket_pairs', []))->values();
        $firstRound = $phase->matches()->where('round_number', 1)->orderBy('bracket_order')->get()->values();
        foreach ($pairs as $index => $pair) {
            $match = $firstRound->get($index);
            if (! $match || in_array($match->status, ['in_progress', 'completed'], true)) {
                continue;
            }
            $match->update([
                'player_one_entry_id' => ($seeds[$pair[0]] ?? null)?->id,
                'player_two_entry_id' => ($seeds[$pair[1]] ?? null)?->id,
                'is_bye' => false,
                'status' => $match->status,
            ]);
        }
        if ($finalizeByes) {
            $firstRound->each(fn ($match) => $this->resolveBye($match->fresh()));
            $this->resolveReadyByes($phase->matches()->pluck('id'));
        }

        return $phase->matches()->count();
    }

    private function qualifiedEntriesFromPhase(FiveQuillasPhase $phase)
    {
        if ($phase->phase_type === 'zones') {
            return $this->qualifiedZoneRows($phase)
                ->sort(function ($left, $right): int {
                    foreach ([
                        [(int) $left->final_place, (int) $right->final_place, 'asc'],
                        [(int) $left->sets_difference, (int) $right->sets_difference, 'desc'],
                        [(float) $left->points_average, (float) $right->points_average, 'desc'],
                        [(int) $left->points_for, (int) $right->points_for, 'desc'],
                    ] as [$a, $b, $direction]) {
                        if ($a !== $b) { return $direction === 'asc' ? $a <=> $b : $b <=> $a; }
                    }
                    return (int) $left->entry_id <=> (int) $right->entry_id;
                })->pluck('entry')->unique('id')->values();
        }
        $stored = collect(data_get($phase->settings, 'qualified_entry_ids', []));
        $byes = collect(data_get($phase->settings, 'play_in_bye_entry_ids', []));
        // Los cruces por horario son independientes y todos sus ganadores pasan a
        // la fase siguiente. No se puede tomar solamente la ronda numéricamente
        // mayor, porque los cruces pueden cargarse y finalizarse por tandas.
        $winnerQuery = $phase->matches()->where('status', 'completed')->whereNotNull('winner_entry_id');
        if ($phase->phase_type !== 'slot_elimination') {
            $lastRound = (int) $phase->matches()->max('round_number');
            $winnerQuery->where('round_number', $lastRound);
        }
        $winners = $winnerQuery->pluck('winner_entry_id')->filter();
        return $phase->competition->entries()->whereIn('id', $stored->merge($byes)->merge($winners)->unique())->get();
    }

    private function qualifiedZoneRows(FiveQuillasPhase $phase)
    {
        return $phase->groups()->with('groupEntries.entry')->get()->flatMap(function ($group) {
            $rows = $group->groupEntries->filter(fn ($row): bool => (bool) $row->entry);
            $qualified = $rows->whereIn('final_place', [1, 2]);
            if ($qualified->count() === 2) {
                return $qualified;
            }

            // Recuperación para zonas antiguas que quedaron marcadas como finalizadas
            // sin haber persistido final_place. Se reconstruye la clasificación con
            // los mismos criterios deportivos mostrados en la tabla de la zona.
            $ordered = $rows->sort(function ($left, $right): int {
                foreach ([
                    [(int) $left->match_wins, (int) $right->match_wins],
                    [(int) $left->sets_difference, (int) $right->sets_difference],
                    [(float) $left->points_average, (float) $right->points_average],
                    [(int) $left->points_for, (int) $right->points_for],
                ] as [$a, $b]) {
                    if ($a !== $b) { return $b <=> $a; }
                }
                return (int) $left->position <=> (int) $right->position;
            })->values();
            $ordered->each(function ($row, int $index): void {
                if (! $row->final_place) { $row->update(['final_place' => $index + 1]); }
            });

            return $ordered->take(2);
        })->filter(fn ($row): bool => (bool) $row->entry)->values();
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
            if ($scores === [] || count($scores) > $bestOf) {
                throw ValidationException::withMessages([
                    'scores' => "Cargá entre 1 y {$bestOf} sets disputados.",
                ]);
            }
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
            $runningOneWins = 0;
            $runningTwoWins = 0;
            foreach ($normalizedScores as $index => $set) {
                if ($runningOneWins >= $winsNeeded || $runningTwoWins >= $winsNeeded) {
                    throw ValidationException::withMessages([
                        'scores' => 'El set '.($index + 1).' no corresponde porque el partido ya tenía un ganador.',
                    ]);
                }
                $set['player_one_points'] > $set['player_two_points'] ? $runningOneWins++ : $runningTwoWins++;
            }

            $isCompleted = max($oneWins, $twoWins) >= $winsNeeded;
            $winnerIsOne = $isCompleted && $oneWins > $twoWins;
            $loserActualSets = min($oneWins, $twoWins);
            $winnerComputedSets = $isCompleted ? $bestOf - $loserActualSets : 0;
            $oneComputed = $isCompleted ? ($winnerIsOne ? $winnerComputedSets : $loserActualSets) : $oneWins;
            $twoComputed = $isCompleted ? ($winnerIsOne ? $loserActualSets : $winnerComputedSets) : $twoWins;
            $onePoints = $normalizedScores->sum('player_one_points');
            $twoPoints = $normalizedScores->sum('player_two_points');
            $winnerId = $isCompleted
                ? ($winnerIsOne ? $match->player_one_entry_id : $match->player_two_entry_id)
                : null;
            $loserId = $isCompleted
                ? ($winnerIsOne ? $match->player_two_entry_id : $match->player_one_entry_id)
                : null;

            $wasCompleted = $match->status === 'completed';
            $previousWinnerId = $match->winner_entry_id;
            if ($match->status === 'completed' && $previousWinnerId && (int) $previousWinnerId !== (int) $winnerId) {
                $dependentMatchIds = collect([$match->next_match_id, $match->loser_next_match_id])->filter()->unique();
                $hasCompletedDependentMatch = FiveQuillasMatch::query()
                    ->whereIn('id', $dependentMatchIds)
                    ->where('status', 'completed')
                    ->where('is_bye', false)
                    ->exists();

                if ($hasCompletedDependentMatch) {
                    throw ValidationException::withMessages([
                        'scores' => 'No se puede cambiar el ganador porque ya se disputó un partido posterior. Corregí primero los resultados posteriores.',
                    ]);
                }

                foreach ($dependentMatchIds as $dependentId) {
                    $this->resetAutomaticByeChain((int) $dependentId);
                }
                $previousLoserId = $this->loserEntryId($match);
                $this->removeAdvancedEntry($match->next_match_id, $match->next_match_position, (int) $previousWinnerId);
                $this->removeAdvancedEntry($match->loser_next_match_id, $match->loser_next_match_position, $previousLoserId);
            }

            $match->update([
                'set_scores' => $this->scoresWithCrossingMetadata($match, [
                    'bye' => false,
                    'sets' => $normalizedScores->all(),
                ]),
                'player_one_actual_sets' => $oneWins,
                'player_two_actual_sets' => $twoWins,
                'player_one_computed_sets' => $oneComputed,
                'player_two_computed_sets' => $twoComputed,
                'player_one_points' => $onePoints,
                'player_two_points' => $twoPoints,
                'winner_entry_id' => $winnerId,
                'status' => $isCompleted ? 'completed' : 'in_progress',
            ]);

            if ($isCompleted) {
                $this->advanceEntry($match->next_match_id, $match->next_match_position, $winnerId);
                $this->advanceEntry($match->loser_next_match_id, $match->loser_next_match_position, $loserId);
                $this->resolveReadyByes(collect([$match->next_match_id, $match->loser_next_match_id])->filter()->unique());
            }
            if ($match->group_id) {
                $match->group()->update(['status' => 'in_progress']);
                if ($wasCompleted && ! $isCompleted) {
                    $match->group->groupEntries()->update(['final_place' => null]);
                    $match->group->groupEntries()->with('entry')->get()
                        ->each(fn ($row) => $row->entry?->update(['status' => 'assigned']));
                }
                $this->recalculateGroupStatistics($match->group_id);
            }
            if (! $isCompleted || $match->phase->status !== 'completed') {
                $match->phase()->update(['status' => 'in_progress']);
                $match->phase->competition()->update(['status' => 'in_progress']);
            }
            if ($isCompleted) {
                $this->completePhaseWhenReady($match->phase_id);
            }

            return $match->fresh();
        });
    }

    public function declareMatchWinner(FiveQuillasMatch $match, int $winnerEntryId): FiveQuillasMatch
    {
        return DB::transaction(function () use ($match, $winnerEntryId): FiveQuillasMatch {
            $match->loadMissing(['phase.competition', 'group']);
            $players = collect([$match->player_one_entry_id, $match->player_two_entry_id])->filter()->map(fn ($id): int => (int) $id);
            if ($match->is_bye || $players->count() !== 2 || ! $players->contains($winnerEntryId)) {
                throw ValidationException::withMessages(['winner' => 'Seleccioná uno de los dos jugadores de la partida.']);
            }
            $previousWinnerId = (int) ($match->winner_entry_id ?? 0);
            if ($previousWinnerId && $previousWinnerId !== $winnerEntryId) {
                $dependentIds = collect([$match->next_match_id, $match->loser_next_match_id])->filter()->unique();
                if (FiveQuillasMatch::query()->whereIn('id', $dependentIds)->where('status', 'completed')->where('is_bye', false)->exists()) {
                    throw ValidationException::withMessages(['winner' => 'Primero reiniciá el partido posterior que ya fue disputado.']);
                }
                foreach ($dependentIds as $dependentId) { $this->resetAutomaticByeChain((int) $dependentId); }
                $this->removeAdvancedEntry($match->next_match_id, $match->next_match_position, $previousWinnerId);
                $this->removeAdvancedEntry($match->loser_next_match_id, $match->loser_next_match_position, $this->loserEntryId($match));
            }
            $loserId = $players->first(fn (int $id): bool => $id !== $winnerEntryId);
            $bestOf = max(1, (int) $match->phase->sets_to_play);
            $winnerIsOne = (int) $match->player_one_entry_id === $winnerEntryId;
            $match->update([
                'set_scores' => $this->scoresWithCrossingMetadata($match, [
                    'manual_winner' => true,
                    'sets' => [],
                    'computed_sets' => $bestOf,
                ]),
                'player_one_actual_sets' => $winnerIsOne ? $bestOf : 0,
                'player_two_actual_sets' => $winnerIsOne ? 0 : $bestOf,
                'player_one_computed_sets' => $winnerIsOne ? $bestOf : 0,
                'player_two_computed_sets' => $winnerIsOne ? 0 : $bestOf,
                'player_one_points' => 0, 'player_two_points' => 0,
                'winner_entry_id' => $winnerEntryId, 'status' => 'completed',
            ]);
            $this->advanceEntry($match->next_match_id, $match->next_match_position, $winnerEntryId);
            $this->advanceEntry($match->loser_next_match_id, $match->loser_next_match_position, $loserId);
            $this->resolveReadyByes(collect([$match->next_match_id, $match->loser_next_match_id])->filter()->unique());
            if ($match->group_id) {
                $match->group()->update(['status' => 'in_progress']);
                $this->recalculateGroupStatistics($match->group_id);
            }
            $match->phase()->update(['status' => 'in_progress']);
            $match->phase->competition()->update(['status' => 'in_progress']);
            $this->completePhaseWhenReady($match->phase_id);
            return $match->fresh();
        });
    }

    public function correctMatchParticipants(FiveQuillasMatch $match, ?int $playerOneId, ?int $playerTwoId): FiveQuillasMatch
    {
        return DB::transaction(function () use ($match, $playerOneId, $playerTwoId): FiveQuillasMatch {
            $match->loadMissing('phase.competition');
            if ((int) $match->round_number !== 1) {
                throw ValidationException::withMessages([
                    'match' => 'Las rondas posteriores se completan exclusivamente con los ganadores de la ronda anterior.',
                ]);
            }
            if (! in_array($match->status, ['pending', 'scheduled'], true)
                && ! ($match->status === 'completed' && $match->is_bye)) {
                throw ValidationException::withMessages(['match' => 'Solo se pueden corregir partidos que todavía no comenzaron.']);
            }
            if ($playerOneId && $playerTwoId && $playerOneId === $playerTwoId) {
                throw ValidationException::withMessages(['match' => 'Un jugador no puede ocupar las dos posiciones del partido.']);
            }
            $allowed = collect($this->bracketParticipantRows($match->phase))->pluck('entry_id')->map(fn ($id): int => (int) $id);
            foreach (collect([$playerOneId, $playerTwoId])->filter() as $entryId) {
                if (! $allowed->contains((int) $entryId)) {
                    throw ValidationException::withMessages(['match' => 'El jugador seleccionado no pertenece a esta fase.']);
                }
            }

            // Una corrección de la primera ronda invalida cualquier pase automático
            // que haya producido. Primero se desarma esa cadena y después se cambia
            // el cruce, para que el jugador anterior desaparezca de la ronda 2.
            $dependentIds = collect([$match->next_match_id, $match->loser_next_match_id])->filter()->unique();
            $playedDependent = FiveQuillasMatch::query()->whereIn('id', $dependentIds)
                ->where('status', 'completed')->where('is_bye', false)->exists();
            if ($playedDependent) {
                throw ValidationException::withMessages([
                    'match' => 'Primero reiniciá el partido posterior que ya fue disputado.',
                ]);
            }
            foreach ($dependentIds as $dependentId) {
                $this->resetAutomaticByeChain((int) $dependentId);
            }
            $previousWinnerId = (int) ($match->winner_entry_id ?? 0);
            $previousLoserId = $this->loserEntryId($match);
            $this->removeAdvancedEntry($match->next_match_id, $match->next_match_position, $previousWinnerId);
            $this->removeAdvancedEntry($match->loser_next_match_id, $match->loser_next_match_position, $previousLoserId);

            // Si el jugador estaba en otro cruce editable de la misma ronda, se lo
            // retira allí para que nunca aparezca duplicado en la grilla.
            foreach (collect([$playerOneId, $playerTwoId])->filter()->unique() as $entryId) {
                $match->phase->matches()->where('round_number', $match->round_number)
                    ->where('id', '!=', $match->id)
                    ->where(function ($query): void {
                        $query->whereIn('status', ['pending', 'scheduled'])
                            ->orWhere(function ($bye): void {
                                $bye->where('status', 'completed')->where('is_bye', true);
                            });
                    })
                    ->where(function ($query) use ($entryId): void {
                        $query->where('player_one_entry_id', $entryId)->orWhere('player_two_entry_id', $entryId);
                    })->get()->each(function ($other) use ($entryId): void {
                        if ($other->status === 'completed' && $other->is_bye) {
                            collect([$other->next_match_id, $other->loser_next_match_id])->filter()->unique()
                                ->each(fn ($dependentId) => $this->resetAutomaticByeChain((int) $dependentId));
                            $this->removeAdvancedEntry($other->next_match_id, $other->next_match_position, (int) $other->winner_entry_id);
                        }
                        $attributes = [];
                        if ((int) $other->player_one_entry_id === (int) $entryId) { $attributes['player_one_entry_id'] = null; }
                        if ((int) $other->player_two_entry_id === (int) $entryId) { $attributes['player_two_entry_id'] = null; }
                        $other->update($attributes + ['is_bye' => false, 'winner_entry_id' => null, 'status' => 'pending']);
                    });
            }

            $match->update([
                'player_one_entry_id' => $playerOneId,
                'player_two_entry_id' => $playerTwoId,
                'winner_entry_id' => null,
                'set_scores' => null,
                'player_one_actual_sets' => 0,
                'player_two_actual_sets' => 0,
                'player_one_computed_sets' => 0,
                'player_two_computed_sets' => 0,
                'player_one_points' => 0,
                'player_two_points' => 0,
                'is_bye' => false,
                'status' => 'pending',
            ]);

            // La corrección puede dejar un único jugador en este cruce o en
            // otro de la primera ronda. Esos pases deben resolverse de nuevo
            // y propagarse por todas las rondas posteriores de la fase.
            $match->phase->matches()->where('round_number', 1)->get()
                ->each(fn ($firstRoundMatch) => $this->resolveBye($firstRoundMatch->fresh()));
            $this->resolveReadyByes($match->phase->matches()->pluck('id'));
            $this->completePhaseWhenReady($match->phase_id);

            return $match->fresh();
        });
    }

    public function finalInstanceForEntry(FiveQuillasCompetition $competition, FiveQuillasEntry $entry): string
    {
        $lastMatch = $competition->phases->where('phase_type', '!=', 'zones')->flatMap->matches
            ->filter(fn ($match): bool => in_array((int) $entry->id, [
                (int) $match->player_one_entry_id, (int) $match->player_two_entry_id,
            ], true))
            ->sortByDesc(fn ($match): string => sprintf('%05d-%05d', $match->phase?->sort_order ?? 0, $match->round_number))
            ->first();

        if ($lastMatch) {
            $phaseName = Str::lower(($lastMatch->phase?->code ?? '').' '.($lastMatch->phase?->name ?? ''));
            if (Str::contains($phaseName, ['nacional', 'national'])) {
                return 'F'.(int) $lastMatch->round_number.' NAC';
            }

            return preg_replace('/\s+\d+$/', '', (string) $lastMatch->label)
                ?: ($lastMatch->phase?->name ?? 'Eliminación');
        }

        $zoneRow = $competition->phases->where('phase_type', 'zones')->flatMap->groups->flatMap->groupEntries
            ->firstWhere('entry_id', $entry->id);

        return $zoneRow ? 'Zonas · '.$zoneRow->final_place.'.º puesto' : 'Sin instancia';
    }

    public function resetMatchResult(FiveQuillasMatch $match): FiveQuillasMatch
    {
        return DB::transaction(function () use ($match): FiveQuillasMatch {
            $match->loadMissing(['phase.competition', 'group.groupEntries.entry']);
            if (! in_array($match->status, ['in_progress', 'completed'], true) || $match->is_bye) {
                throw ValidationException::withMessages([
                    'match' => 'Solamente se pueden reiniciar partidos con resultados cargados.',
                ]);
            }

            $dependentIds = collect([$match->next_match_id, $match->loser_next_match_id])->filter()->unique();
            $completedDependent = FiveQuillasMatch::query()
                ->whereIn('id', $dependentIds)
                ->where('status', 'completed')
                ->where('is_bye', false)
                ->exists();
            if ($completedDependent) {
                throw ValidationException::withMessages([
                    'match' => 'Primero tenés que reiniciar los partidos posteriores que ya fueron disputados.',
                ]);
            }

            foreach ($dependentIds as $dependentId) {
                $this->resetAutomaticByeChain((int) $dependentId);
            }

            $nextPrepared = $match->phase->competition->phases()
                ->where('sort_order', '>', $match->phase->sort_order)
                ->whereHas('matches')->exists();
            if ($match->phase->status === 'completed' && $nextPrepared) {
                throw ValidationException::withMessages([
                    'match' => 'La fase siguiente ya fue preparada. Reiniciá primero sus partidos para poder corregir esta fase.',
                ]);
            }

            $winnerId = (int) $match->winner_entry_id;
            $loserId = $this->loserEntryId($match);
            $this->removeAdvancedEntry($match->next_match_id, $match->next_match_position, $winnerId);
            $this->removeAdvancedEntry($match->loser_next_match_id, $match->loser_next_match_position, $loserId);

            $match->update([
                'set_scores' => $this->scoresWithCrossingMetadata($match, []),
                'player_one_actual_sets' => 0,
                'player_two_actual_sets' => 0,
                'player_one_computed_sets' => 0,
                'player_two_computed_sets' => 0,
                'player_one_points' => 0,
                'player_two_points' => 0,
                'winner_entry_id' => null,
                'table_number' => null,
                'is_bye' => false,
                'status' => 'pending',
            ]);

            $match->phase()->update(['status' => 'in_progress']);
            $match->phase->competition()->update(['status' => 'in_progress']);
            $match->phase->competition->entries()->update(['final_position' => null]);
            if ($match->group_id) {
                $match->group()->update(['status' => 'confirmed']);
                $match->group->groupEntries()->update(['final_place' => null]);
                $match->group->groupEntries->each(fn ($row) => $row->entry?->update(['status' => 'assigned']));
                $this->recalculateGroupStatistics($match->group_id);
            }

            return $match->fresh();
        });
    }

    private function completePhaseWhenReady(int $phaseId): void
    {
        $phase = FiveQuillasPhase::query()->with('competition.tournament.type')->findOrFail($phaseId);
        if ($phase->phase_type === 'slot_elimination') {
            if (! $this->slotEliminationHasEveryCrossing($phase)) {
                $phase->update(['status' => 'in_progress']);
                $phase->competition()->update(['status' => 'in_progress']);
                return;
            }
        }
        if ($phase->matches()->exists() && $phase->matches()->where('status', '!=', 'completed')->doesntExist()) {
            $updates = ['status' => 'completed'];
            if ($phase->phase_type === 'slot_elimination') {
                $winnerIds = $phase->matches()->whereNotNull('winner_entry_id')
                    ->pluck('winner_entry_id')->map(fn ($id): int => (int) $id)->unique()->values();
                $limit = (int) data_get($phase->settings, 'qualifiers', 0);
                if ($limit > 0) {
                    $winnerIds = $winnerIds->take($limit);
                }
                $updates['settings'] = array_merge($phase->settings ?? [], [
                    'qualified_entry_ids' => $winnerIds->all(),
                ]);
            }
            $phase->update($updates);
            $nextId = (int) data_get($phase->settings, 'next_phase_id', 0);
            $next = $nextId > 0
                ? $phase->competition->phases()->where('is_enabled', true)->find($nextId)
                : null;
            $next ??= $phase->competition->phases()->where('is_enabled', true)
                ->where('sort_order', '>', $phase->sort_order)->orderBy('sort_order')->first();
            if (! $next) {
                $phase->competition->update(['status' => 'completed']);
                $this->assignFinalPoints($phase);
            } else {
                $this->assignPhaseEliminationPoints($phase);
            }
        } elseif ($phase->matches()->where('status', 'completed')->exists()) {
            $phase->update(['status' => 'in_progress']);
            $phase->competition()->update(['status' => 'in_progress']);
        }
    }

    public function reopenIncompleteConcurrentPhases(FiveQuillasCompetition $competition): void
    {
        $competition->phases()->where('phase_type', 'zones')
            ->where('is_enabled', true)->where('status', 'completed')->get()
            ->each(function (FiveQuillasPhase $phase) use ($competition): void {
                if ($this->zonesHaveEveryScheduledPlayer($phase)) {
                    return;
                }

                $phase->update(['status' => 'in_progress']);
                if ($competition->status === 'completed') {
                    $competition->update(['status' => 'in_progress']);
                }
            });

        $competition->phases()->where('phase_type', 'slot_elimination')
            ->where('is_enabled', true)->get()
            ->each(function (FiveQuillasPhase $phase) use ($competition): void {
                if ($this->slotEliminationHasEveryCrossing($phase)) {
                    if ($phase->status !== 'completed') {
                        $this->completePhaseWhenReady($phase->id);
                    }

                    return;
                }

                if ($phase->status === 'completed') {
                    $phase->update(['status' => 'in_progress']);
                    if ($competition->status === 'completed') {
                        $competition->update(['status' => 'in_progress']);
                    }
                }
            });
    }

    private function zonesHaveEveryScheduledPlayer(FiveQuillasPhase $phase): bool
    {
        $expectedEntryIds = $phase->competition->entries()
            ->where('entry_phase_id', $phase->id)
            ->whereNotNull('source_slot_id')
            ->where('status', '!=', 'withdrawn')
            ->pluck('id')->map(fn ($id): int => (int) $id)->unique();
        if ($expectedEntryIds->isEmpty()) {
            return false;
        }

        $groups = $phase->groups()->with('groupEntries:id,group_id,entry_id')->get();
        if ($groups->isEmpty() || $groups->contains(fn ($group): bool => $group->status !== 'completed')) {
            return false;
        }

        $assignedEntryIds = $groups->flatMap->groupEntries->pluck('entry_id')
            ->map(fn ($id): int => (int) $id)->unique();

        return $expectedEntryIds->diff($assignedEntryIds)->isEmpty();
    }

    private function slotEliminationHasEveryCrossing(FiveQuillasPhase $phase): bool
    {
        if ($phase->matches()->doesntExist()
            || $phase->matches()->where('status', '!=', 'completed')->exists()) {
            return false;
        }

        $qualifiers = (int) data_get($phase->settings, 'qualifiers', 0);
        if ($qualifiers > 0) {
            $winnerCount = $phase->matches()->where('status', 'completed')
                ->whereNotNull('winner_entry_id')->distinct()->count('winner_entry_id');

            return $winnerCount >= $qualifiers;
        }

        $previous = $this->previousPhase($phase);
        if (! $previous || $previous->phase_type !== 'zones' || $previous->status !== 'completed') {
            return false;
        }

        $groupIds = $previous->groups()->pluck('id')->map(fn ($id): int => (int) $id)->unique();
        if ($groupIds->isEmpty()) {
            return false;
        }

        $crossings = collect(data_get($phase->settings, 'slot_crossings', []));
        $firstGroupIds = $crossings->pluck('first_group_id')->map(fn ($id): int => (int) $id)->filter()->unique();
        $secondGroupIds = $crossings->pluck('second_group_id')->map(fn ($id): int => (int) $id)->filter()->unique();

        return $groupIds->diff($firstGroupIds)->isEmpty()
            && $groupIds->diff($secondGroupIds)->isEmpty();
    }

    public function recalculateFinalStandings(FiveQuillasCompetition $competition): void
    {
        DB::transaction(function () use ($competition): void {
            $competition->entries()->update(['final_position' => null]);
            $phases = $competition->phases()->where('is_enabled', true)
                ->where('status', 'completed')->orderBy('sort_order')->get();

            foreach ($phases as $phase) {
                $hasLaterPhase = $competition->phases()->where('is_enabled', true)
                    ->where('sort_order', '>', $phase->sort_order)->exists();

                if (! $hasLaterPhase && $phase->phase_type === 'bracket') {
                    $this->assignFinalPoints($phase);
                } else {
                    $this->assignPhaseEliminationPoints($phase);
                }
            }
        });
    }

    public function ensureFinalStandingsOrder(FiveQuillasCompetition $competition): void
    {
        if ((int) data_get($competition->settings, 'standings_order_version', 0) >= 4) {
            return;
        }

        $this->normalizeByeScores($competition);
        $this->recalculateFinalStandings($competition);
        $competition->update([
            'settings' => array_merge($competition->settings ?? [], ['standings_order_version' => 4]),
        ]);
    }

    private function normalizeByeScores(FiveQuillasCompetition $competition): void
    {
        $groupIds = collect();
        $matches = FiveQuillasMatch::query()->with('phase')
            ->whereHas('phase', fn ($query) => $query->where('competition_id', $competition->id))
            ->where('status', 'completed')
            ->where(fn ($query) => $query
                ->where(fn ($one) => $one->whereNotNull('player_one_entry_id')->whereNull('player_two_entry_id'))
                ->orWhere(fn ($two) => $two->whereNull('player_one_entry_id')->whereNotNull('player_two_entry_id')))
            ->get();

        foreach ($matches as $match) {
            $bestOf = (int) $match->phase->sets_to_play;
            $winsNeeded = intdiv($bestOf, 2) + 1;
            $winnerIsOne = filled($match->player_one_entry_id);
            $match->update([
                'winner_entry_id' => $winnerIsOne ? $match->player_one_entry_id : $match->player_two_entry_id,
                'set_scores' => ['bye' => true, 'sets' => []],
                'player_one_actual_sets' => $winnerIsOne ? $winsNeeded : 0,
                'player_two_actual_sets' => $winnerIsOne ? 0 : $winsNeeded,
                'player_one_computed_sets' => $winnerIsOne ? $bestOf : 0,
                'player_two_computed_sets' => $winnerIsOne ? 0 : $bestOf,
                'player_one_points' => 0,
                'player_two_points' => 0,
                'is_bye' => true,
            ]);
            if ($match->group_id) {
                $groupIds->push((int) $match->group_id);
            }
        }

        $groupIds->unique()->each(fn (int $groupId) => $this->recalculateGroupStatistics($groupId));
    }

    public function finalStandingPerformance(FiveQuillasCompetition $competition, int $entryId): array
    {
        $phase = $competition->phases()
            ->where('phase_type', '!=', 'zones')
            ->whereHas('matches', fn ($query) => $query
                ->where(fn ($players) => $players->where('player_one_entry_id', $entryId)
                    ->orWhere('player_two_entry_id', $entryId)))
            ->orderByDesc('sort_order')
            ->first();

        if ($phase) {
            return $this->entryPerformance(
                $phase->matches()->where('status', 'completed')->get(),
                $entryId,
            );
        }

        $zoneRow = FiveQuillasGroupEntry::query()->where('entry_id', $entryId)
            ->whereHas('group.phase', fn ($query) => $query
                ->where('competition_id', $competition->id)->where('phase_type', 'zones'))
            ->first();

        return [
            'wins' => (int) ($zoneRow?->match_wins ?? 0),
            'sets_difference' => (int) ($zoneRow?->sets_difference ?? 0),
            'average' => (float) ($zoneRow?->points_average ?? 0),
            'points_for' => (int) ($zoneRow?->points_for ?? 0),
        ];
    }

    private function assignFinalPoints(FiveQuillasPhase $phase): void
    {
        if ($phase->phase_type !== 'bracket') {
            return;
        }

        $matches = $phase->matches()->with('winner')->get();
        $lastRound = (int) $matches->max('round_number');
        $final = $matches->where('round_number', $lastRound)->first();
        if (! $final?->winner_entry_id) { return; }

        $placements = collect([(int) $final->winner_entry_id => 1]);
        $finalLoser = $this->loserEntryId($final);
        if ($finalLoser) { $placements->put($finalLoser, 2); }
        $position = 3;
        for ($round = $lastRound - 1; $round >= 1; $round--) {
            $roundLosers = $matches->where('round_number', $round)->map(fn ($match) => $this->loserEntryId($match))
                ->filter()->unique()->sort(function (int $left, int $right) use ($matches): int {
                    $leftStats = $this->entryPerformance($matches, $left);
                    $rightStats = $this->entryPerformance($matches, $right);
                    // Todos los jugadores de este bloque fueron eliminados en la
                    // misma ronda. El orden deportivo se define exclusivamente
                    // por diferencia de sets y, en caso de igualdad, promedio.
                    foreach (['sets_difference', 'average'] as $field) {
                        if ($leftStats[$field] !== $rightStats[$field]) {
                            return $rightStats[$field] <=> $leftStats[$field];
                        }
                    }
                    return $left <=> $right;
                });
            foreach ($roundLosers as $loser) {
                if (! $placements->has($loser)) { $placements->put($loser, $position++); }
            }
        }

        $entries = $phase->competition->entries()->with('registration')->whereIn('id', $placements->keys())->get()->keyBy('id');
        foreach ($placements as $entryId => $place) {
            $entry = $entries->get($entryId);
            if (! $entry) { continue; }
            $entry->update([
                'final_position' => $place,
                'status' => $place === 1 ? 'qualified' : 'eliminated',
            ]);
        }
    }

    private function assignPhaseEliminationPoints(FiveQuillasPhase $phase): void
    {
        $qualified = $this->qualifiedEntriesFromPhase($phase)->pluck('id')->map(fn ($id): int => (int) $id);
        $futureEntrants = $phase->competition->entries()
            ->whereHas('entryPhase', fn ($query) => $query->where('sort_order', '>', $phase->sort_order))
            ->where('status', '!=', 'withdrawn')->pluck('id')->map(fn ($id): int => (int) $id);
        $stillCompeting = $qualified->merge($futureEntrants)->unique();

        $participantIds = $phase->phase_type === 'zones'
            ? $phase->groups()->with('groupEntries')->get()->flatMap->groupEntries->pluck('entry_id')
            : $phase->matches()->get()->flatMap(fn ($match): array => [
                $match->player_one_entry_id, $match->player_two_entry_id,
            ])->filter();
        $eliminated = $phase->competition->entries()->whereIn('id', $participantIds->unique())
            ->whereNotIn('id', $qualified)->where('status', '!=', 'withdrawn')->get();
        if ($eliminated->isEmpty()) {
            return;
        }

        $matches = $phase->matches()->where('status', 'completed')->get();
        $zoneRows = $phase->phase_type === 'zones'
            ? $phase->groups()->with('groupEntries')->get()->flatMap->groupEntries->keyBy('entry_id')
            : collect();
        $ordered = $eliminated->sort(function ($left, $right) use ($matches, $zoneRows, $phase): int {
            if ($phase->phase_type === 'zones') {
                $leftRow = $zoneRows->get($left->id);
                $rightRow = $zoneRows->get($right->id);
                foreach ([
                    [(int) ($leftRow?->final_place ?? 99), (int) ($rightRow?->final_place ?? 99), 'asc'],
                    [(int) ($leftRow?->sets_difference ?? 0), (int) ($rightRow?->sets_difference ?? 0), 'desc'],
                    [(float) ($leftRow?->points_average ?? 0), (float) ($rightRow?->points_average ?? 0), 'desc'],
                ] as [$a, $b, $direction]) {
                    if ($a !== $b) { return $direction === 'asc' ? $a <=> $b : $b <=> $a; }
                }

                return (int) $left->id <=> (int) $right->id;
            }

            // Una eliminación en una ronda posterior siempre representa una
            // mejor posición. Dentro de la misma ronda se aplican DS y promedio.
            $leftRound = $this->lastRoundPlayed($matches, (int) $left->id);
            $rightRound = $this->lastRoundPlayed($matches, (int) $right->id);
            if ($leftRound !== $rightRound) {
                return $rightRound <=> $leftRound;
            }

            $leftStats = $this->entryPerformance($matches, (int) $left->id);
            $rightStats = $this->entryPerformance($matches, (int) $right->id);
            foreach (['sets_difference', 'average'] as $field) {
                if ($leftStats[$field] !== $rightStats[$field]) {
                    return $rightStats[$field] <=> $leftStats[$field];
                }
            }
            return ($left->national_ranking_snapshot ?: PHP_INT_MAX)
                <=> ($right->national_ranking_snapshot ?: PHP_INT_MAX);
        })->values();

        $position = $stillCompeting->count() + 1;
        foreach ($ordered as $entry) {
            $place = $position++;
            $entry->update(['status' => 'eliminated', 'final_position' => $place]);
        }
    }

    private function lastRoundPlayed($matches, int $entryId): int
    {
        return (int) $matches
            ->filter(fn ($match): bool => in_array($entryId, [
                (int) $match->player_one_entry_id,
                (int) $match->player_two_entry_id,
            ], true))
            ->max('round_number');
    }

    private function entryPerformance($matches, int $entryId): array
    {
        $wins = 0; $setsFor = 0; $setsAgainst = 0; $pointsFor = 0; $pointsAgainst = 0;
        foreach ($matches as $match) {
            $isOne = (int) $match->player_one_entry_id === $entryId;
            $isTwo = (int) $match->player_two_entry_id === $entryId;
            if (! $isOne && ! $isTwo) { continue; }
            $wins += (int) $match->winner_entry_id === $entryId ? 1 : 0;
            $setsFor += $isOne ? (int) $match->player_one_computed_sets : (int) $match->player_two_computed_sets;
            $setsAgainst += $isOne ? (int) $match->player_two_computed_sets : (int) $match->player_one_computed_sets;
            $pointsFor += $isOne ? (int) $match->player_one_points : (int) $match->player_two_points;
            $pointsAgainst += $isOne ? (int) $match->player_two_points : (int) $match->player_one_points;
        }
        return [
            'wins' => $wins,
            'sets_difference' => $setsFor - $setsAgainst,
            'average' => $pointsAgainst > 0 ? $pointsFor / $pointsAgainst : 0,
            'points_for' => $pointsFor,
        ];
    }

    private function orderByPreviousPhaseResult(FiveQuillasPhase $phase, $source)
    {
        $previous = $this->previousPhase($phase);
        if (! $previous || $previous->phase_type !== 'zones') {
            return collect($source)->values();
        }

        $rows = $previous->groups()->with('groupEntries')->get()->flatMap->groupEntries->keyBy('entry_id');
        return collect($source)->sort(function ($left, $right) use ($rows): int {
            $a = $rows->get($left->id);
            $b = $rows->get($right->id);
            foreach ([
                [(int) ($a?->final_place ?? 99), (int) ($b?->final_place ?? 99), 'asc'],
                [(int) ($a?->sets_difference ?? 0), (int) ($b?->sets_difference ?? 0), 'desc'],
                [(float) ($a?->points_average ?? 0), (float) ($b?->points_average ?? 0), 'desc'],
                [(int) ($a?->points_for ?? 0), (int) ($b?->points_for ?? 0), 'desc'],
            ] as [$leftValue, $rightValue, $direction]) {
                if ($leftValue !== $rightValue) {
                    return $direction === 'asc' ? $leftValue <=> $rightValue : $rightValue <=> $leftValue;
                }
            }
            return (int) $left->id <=> (int) $right->id;
        })->values();
    }

    private function eliminationRoundLabel(int $bracketSize, int $round): string
    {
        $players = max(2, intdiv($bracketSize, 2 ** ($round - 1)));
        return match ($players) {
            2 => 'Final',
            4 => 'Semifinal',
            8 => 'Cuartos de final',
            16 => 'Octavos de final',
            32 => 'Dieciseisavos de final',
            64 => 'Treintaidosavos de final',
            default => 'Ronda de '.$players,
        };
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

    private function ruleForEntryPlacement(array $rules, FiveQuillasCompetition $competition, FiveQuillasEntry $entry): ?array
    {
        $instance = Str::lower(Str::ascii($this->finalInstanceForEntry($competition, $entry)));
        $instance = preg_replace('/[^a-z0-9]+/', ' ', $instance) ?: '';

        // En Circuito Argentino las instancias F1 NAC y F2 NAC identifican
        // la ronda Nacional exacta. Si la tabla de puntuación usa esos códigos,
        // tienen prioridad sobre un rango numérico genérico.
        if (preg_match('/\bf[12]\s+nac\b/', $instance, $stage)) {
            foreach ($rules as $rule) {
                $ruleText = Str::lower(Str::ascii(
                    (string) ($rule['code'] ?? '').' '.(string) ($rule['description'] ?? '')
                ));
                $ruleText = preg_replace('/[^a-z0-9]+/', ' ', $ruleText) ?: '';
                if (preg_match('/\b'.str_replace(' ', '\\s+', preg_quote($stage[0], '/')).'\b/', $ruleText)) {
                    return $rule;
                }
            }
        }

        return $this->ruleForPlacement($rules, (int) $entry->final_position);
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

    private function removeAdvancedEntry(?int $matchId, ?int $position, ?int $entryId): void
    {
        if (! $matchId || ! $entryId || ! in_array($position, [1, 2], true)) {
            return;
        }

        $field = $position === 1 ? 'player_one_entry_id' : 'player_two_entry_id';
        FiveQuillasMatch::query()->whereKey($matchId)->where($field, $entryId)->update([
            $field => null,
            'table_number' => null,
            'status' => 'pending',
        ]);
    }

    private function resetAutomaticByeChain(int $matchId): void
    {
        $match = FiveQuillasMatch::query()->find($matchId);
        if (! $match || $match->status !== 'completed' || ! $match->is_bye) {
            return;
        }

        $dependentIds = collect([$match->next_match_id, $match->loser_next_match_id])->filter()->unique();
        $hasPlayedDependent = FiveQuillasMatch::query()
            ->whereIn('id', $dependentIds)
            ->where('status', 'completed')
            ->where('is_bye', false)
            ->exists();
        if ($hasPlayedDependent) {
            throw ValidationException::withMessages([
                'match' => 'Primero tenés que reiniciar los partidos posteriores que ya fueron disputados.',
            ]);
        }

        foreach ($dependentIds as $dependentId) {
            $this->resetAutomaticByeChain((int) $dependentId);
        }

        $winnerId = (int) $match->winner_entry_id;
        $loserId = $this->loserEntryId($match);
        $this->removeAdvancedEntry($match->next_match_id, $match->next_match_position, $winnerId);
        $this->removeAdvancedEntry($match->loser_next_match_id, $match->loser_next_match_position, $loserId);

        $match->update([
            'set_scores' => null,
            'player_one_actual_sets' => 0,
            'player_two_actual_sets' => 0,
            'player_one_computed_sets' => 0,
            'player_two_computed_sets' => 0,
            'player_one_points' => 0,
            'player_two_points' => 0,
            'winner_entry_id' => null,
            'table_number' => null,
            'is_bye' => false,
            'status' => 'pending',
        ]);
    }

    private function resolveReadyByes($matchIds): void
    {
        $ids = collect($matchIds)->map(fn ($id): int => (int) $id)->unique()->values();

        // Repite el recorrido porque un pase libre resuelto puede habilitar otro
        // pase libre en la ronda siguiente.
        do {
            $resolved = 0;
            $candidates = FiveQuillasMatch::query()->whereIn('id', $ids)
                ->orderBy('round_number')->orderBy('bracket_order')->get();

            foreach ($candidates as $candidate) {
                if ($candidate->status === 'completed') {
                    continue;
                }
                $sourceMatches = FiveQuillasMatch::query()
                    ->where('next_match_id', $candidate->id)
                    ->orWhere('loser_next_match_id', $candidate->id)
                    ->get();
                if ($sourceMatches->isNotEmpty() && $sourceMatches->every(fn ($source): bool => $source->status === 'completed')) {
                    $before = $candidate->status;
                    $this->resolveBye($candidate);
                    $candidate->refresh();
                    $resolved += $before !== 'completed' && $candidate->status === 'completed' ? 1 : 0;
                }
            }
        } while ($resolved > 0);
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

        $matchesByLabel = $group->matches->keyBy('label');
        $firstMatch = $matchesByLabel->get('Definición del primer puesto');
        $secondMatch = $matchesByLabel->get('Definición del segundo puesto');
        $definedPlaces = collect();
        if ($firstMatch?->status === 'completed' && $firstMatch->winner_entry_id) {
            $definedPlaces->put((int) $firstMatch->winner_entry_id, 1);
        }
        if ($secondMatch?->status === 'completed' && $secondMatch->winner_entry_id) {
            $definedPlaces->put((int) $secondMatch->winner_entry_id, 2);
        }
        foreach ($definedPlaces as $entryId => $place) {
            $group->groupEntries()->where('entry_id', $entryId)->update(['final_place' => $place]);
            $group->groupEntries()->where('entry_id', '!=', $entryId)
                ->where('final_place', $place)->update(['final_place' => null]);
            $group->groupEntries->firstWhere('entry_id', $entryId)?->entry()
                ->update(['status' => 'qualified']);
        }
        $group->load('groupEntries');
        $this->refreshConfiguredSlotCrossings($group);

        if ($group->matches->isNotEmpty() && $group->matches->every(fn ($match): bool => $match->status === 'completed')) {
            $recoveryMatch = $matchesByLabel->get('Perdedores')
                ?? $matchesByLabel->get('Partido de recuperación');
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
                $zonePhase = $group->phase()->first();
                if ($this->zonesHaveEveryScheduledPlayer($zonePhase)) {
                    $zonePhase->update(['status' => 'completed']);
                    $this->assignPhaseEliminationPoints($zonePhase);
                } else {
                    $zonePhase->update(['status' => 'in_progress']);
                    $zonePhase->competition()->update(['status' => 'in_progress']);
                }
            }
        }
    }

    private function refreshConfiguredSlotCrossings(FiveQuillasGroup $group): void
    {
        $phases = $group->phase->competition->phases()->where('phase_type', 'slot_elimination')
            ->where('is_enabled', true)->get();

        foreach ($phases as $phase) {
            if ((int) ($this->previousPhase($phase)?->id ?? 0) !== (int) $group->phase_id) {
                continue;
            }
            $crossings = collect(data_get($phase->settings, 'slot_crossings', []));
            foreach ($crossings as $index => $crossing) {
                $attributes = [];
                if ((int) ($crossing['first_group_id'] ?? 0) === (int) $group->id) {
                    $attributes['player_one_entry_id'] = $group->groupEntries
                        ->firstWhere('final_place', 1)?->entry_id;
                }
                if ((int) ($crossing['second_group_id'] ?? 0) === (int) $group->id) {
                    $attributes['player_two_entry_id'] = $group->groupEntries
                        ->firstWhere('final_place', 2)?->entry_id;
                }
                if ($attributes !== []) {
                    $phase->matches()->where('round_number', 1)
                        ->where('bracket_order', $index + 1)
                        ->whereIn('status', ['pending', 'scheduled'])
                        ->update($attributes);
                }
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
        // Todas las zonas pertenecientes al mismo horario comienzan juntas.
        // La mesa se asigna manualmente al llamar cada partido.
        $slotStartsAt = $group->slot()->value('starts_at');
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
            'label' => 'Perdedores', 'next_match_id' => $secondPlace->id,
            'next_match_position' => 2, 'is_public' => $public,
        ]);
        $matchOne = $group->matches()->create([
            'phase_id' => $phase->id, 'round_number' => 1, 'bracket_order' => 1,
            'label' => 'Partido 1', 'player_one_entry_id' => $entries->get(1)?->id,
            'player_two_entry_id' => $entries->get(2)?->id, 'next_match_id' => $winnersFinal->id,
            'next_match_position' => 1, 'loser_next_match_id' => $losersMatch->id,
            'loser_next_match_position' => 1, 'is_public' => $public,
            'scheduled_at' => $slotStartsAt,
        ]);
        $matchTwo = $group->matches()->create([
            'phase_id' => $phase->id, 'round_number' => 1, 'bracket_order' => 2,
            'label' => 'Partido 2', 'player_one_entry_id' => $entries->get(3)?->id,
            'player_two_entry_id' => $entries->get(4)?->id, 'next_match_id' => $winnersFinal->id,
            'next_match_position' => 2, 'loser_next_match_id' => $losersMatch->id,
            'loser_next_match_position' => 2, 'is_public' => $public,
            'scheduled_at' => $slotStartsAt,
        ]);
        $this->resolveBye($matchOne);
        $this->resolveBye($matchTwo);
    }

    private function resolveBye($match): void
    {
        $players = collect([$match->player_one_entry_id, $match->player_two_entry_id])->filter();
        if ($players->count() > 1) {
            return;
        }

        if ($players->isEmpty()) {
            $match->update([
                'winner_entry_id' => null,
                'set_scores' => ['bye' => true, 'sets' => []],
                'player_one_actual_sets' => 0,
                'player_two_actual_sets' => 0,
                'player_one_computed_sets' => 0,
                'player_two_computed_sets' => 0,
                'is_bye' => true,
                'status' => 'completed',
            ]);

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

    private function phaseTemplate(Tournament $tournament, string $format = 'zones_final'): array
    {
        if ($format === 'circuit_argentino') {
            $categories = Category::query()->where('discipline_id', $tournament->discipline_id)->get();
            $masters = $categories->filter(fn ($category): bool => Str::contains(Str::lower($category->code.' '.$category->name), 'master'))->pluck('id')->all();
            $nationals = $categories->filter(fn ($category): bool => Str::contains(Str::lower($category->code.' '.$category->name), ['1ra', '1°', 'primera', 'nacional']))->pluck('id')->all();
            $initial = $categories->whereNotIn('id', array_merge($masters, $nationals))->pluck('id')->all();
            return [
                $this->phase('zones', 'Zonas', 'zones', 1, 3, 'manual', true, ['entry_category_ids' => $initial]),
                $this->phase('slot-elimination', 'Eliminación por horario', 'slot_elimination', 2, 3, 'manual', true),
                $this->phase('national-play-in', 'Emparejamientos de Primera Nacional', 'play_in', 3, 3, 'national_ranking', true),
                $this->phase('national', 'Primera Nacional', 'bracket', 4, 5, 'national_ranking', true, [
                    'entry_category_ids' => $nationals,
                    'direct_entry_seed_count' => 32,
                    'bracket_size' => 64,
                    'stop_after_rounds' => 2,
                    'bracket_pairs' => $this->officialBracketPairs(64),
                    'schedules' => [
                        ['name' => 'Horario 1', 'starts_at' => null, 'round_number' => 1, 'bracket_section' => '1-64 a 61-4', 'from_match' => 1, 'to_match' => 16, 'tables' => null],
                        ['name' => 'Horario 2', 'starts_at' => null, 'round_number' => 1, 'bracket_section' => '3-62 a 2-63', 'from_match' => 17, 'to_match' => 32, 'tables' => null],
                    ],
                ]),
                $this->phase('master-final', 'Master / Etapa final', 'bracket', 5, 5, 'national_ranking', false, [
                    'entry_category_ids' => $masters,
                    'direct_entry_seed_count' => 16,
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
            2 => [[1,2]],
            4 => [[1,4],[2,3]],
            8 => [[1,8],[4,5],[3,6],[7,2]],
            16 => [[1,16],[9,8],[5,12],[13,4],[3,14],[11,6],[7,10],[15,2]],
            32 => [[1,32],[16,17],[9,24],[25,8],[5,28],[12,21],[13,20],[29,4],
                [3,30],[14,19],[11,22],[27,6],[7,26],[10,23],[15,18],[31,2]],
            64 => [[1,64],[32,33],[17,48],[49,16],[9,56],[24,41],[25,40],[57,8],
                [5,60],[28,37],[12,53],[21,44],[13,52],[20,45],[29,36],[61,4],
                [3,62],[30,35],[19,46],[51,14],[11,54],[43,22],[27,38],[59,6],
                [7,58],[39,26],[23,42],[55,10],[15,50],[47,18],[31,34],[63,2]],
            128, 256 => $this->generatedBracketPairs($size),
            default => throw ValidationException::withMessages([
                'phases' => 'El cuadro debe tener 2, 4, 8, 16, 32, 64, 128 o 256 posiciones.',
            ]),
        };
    }

    private function previousPhase(FiveQuillasPhase $phase): ?FiveQuillasPhase
    {
        $configuredId = (int) data_get($phase->settings, 'previous_phase_id', 0);
        if ($configuredId > 0) {
            $configured = $phase->competition->phases()->where('is_enabled', true)->find($configuredId);
            if ($configured) {
                return $configured;
            }
        }

        return $phase->competition->phases()->where('is_enabled', true)
            ->where('sort_order', '<', $phase->sort_order)->orderByDesc('sort_order')->first();
    }

    private function generatedBracketPairs(int $size): array
    {
        $order = [1, 2];
        while (count($order) < $size) {
            $sum = count($order) * 2 + 1;
            $next = [];
            foreach ($order as $seed) {
                $next[] = $seed;
                $next[] = $sum - $seed;
            }
            $order = $next;
        }

        return collect($order)->chunk(2)->map(fn ($pair): array => $pair->values()->all())->all();
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
