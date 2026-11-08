<?php

namespace App\Services;

use App\Models\FiveQuillasCompetition;
use App\Models\Tournament;
use App\Models\TournamentInstance;
use App\Models\TournamentRegistration;
use App\Models\TournamentType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Resuelve reglas de puntuación, propuestas y asignaciones persistidas de torneos.
 */
class TournamentScoringService
{
    /**
     * Asigna una misma posición o resultado a varias inscripciones.
     *
     * Todos los cambios se guardan dentro de una única transacción. Si una
     * inscripción no pertenece al torneo, está repetida o alguna regla falla,
     * no se modifica ningún registro.
     *
     * @param  array<int, array{registration_id:int, penalty_points?:float|int|string|null}>  $items
     * @return Collection<int, TournamentRegistration>
     */
    public function assignBatch(
        Tournament $tournament,
        array $items,
        ?int $tournamentInstanceId = null,
        ?string $resultCode = null,
    ): Collection {
        $tournament->loadMissing('type');
        $this->validateTournamentType($tournament);

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Agregá al menos un jugador o pareja.',
            ]);
        }

        $registrationIds = collect($items)
            ->pluck('registration_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($registrationIds->count() !== count($items)) {
            throw ValidationException::withMessages([
                'items' => 'Cada fila debe tener una inscripción seleccionada.',
            ]);
        }

        if ($registrationIds->unique()->count() !== $registrationIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Una inscripción no puede agregarse más de una vez.',
            ]);
        }

        $usesOfficialInstances = $this->usesOfficialInstances($tournament);

        if ($usesOfficialInstances && ! $tournamentInstanceId) {
            throw ValidationException::withMessages([
                'tournament_instance_id' => 'Seleccioná una posición oficial.',
            ]);
        }

        if (! $usesOfficialInstances && blank($resultCode)) {
            throw ValidationException::withMessages([
                'result_code' => 'Seleccioná un resultado.',
            ]);
        }

        return DB::transaction(function () use (
            $tournament,
            $items,
            $registrationIds,
            $usesOfficialInstances,
            $tournamentInstanceId,
            $resultCode,
        ): Collection {
            $registrations = TournamentRegistration::query()
                ->where('tournament_id', $tournament->getKey())
                ->whereKey($registrationIds)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (TournamentRegistration $registration): int => (int) $registration->getKey());

            if ($registrations->count() !== $registrationIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Una o más inscripciones no pertenecen al torneo seleccionado.',
                ]);
            }

            if ($registrations->contains(
                fn (TournamentRegistration $registration): bool => $registration->points !== null
            )) {
                throw ValidationException::withMessages([
                    'items' => 'Una o más inscripciones ya tienen una puntuación asignada.',
                ]);
            }

            $updated = new Collection;

            foreach ($items as $item) {
                $registration = $registrations->get((int) $item['registration_id']);
                $penaltyPoints = (float) ($item['penalty_points'] ?? 0);

                if ($penaltyPoints < 0) {
                    throw ValidationException::withMessages([
                        'items' => 'La penalización no puede ser negativa.',
                    ]);
                }

                $registration->penalty_points = $penaltyPoints;

                $updated->push(
                    $usesOfficialInstances
                        ? $this->assignByTournamentInstance($registration, (int) $tournamentInstanceId)
                        : $this->assignByCode($registration, (string) $resultCode)
                );
            }

            return $updated;
        });
    }

    /**
     * Obtiene las reglas aplicables al torneo.
     *
     * La tabla del tipo de torneo es la fuente única de puntuaciones.
     */
    public function getRules(Tournament $tournament): array
    {
        $tournament->loadMissing('type');

        $typeRules = $tournament->type?->scoring_rules;

        if (! is_array($typeRules)) {
            return [];
        }

        return $this->orderedRules($typeRules);
    }

    private function orderedRules(array $rules): array
    {
        $rules = array_values($rules);
        usort(
            $rules,
            fn (array $left, array $right): int => strnatcasecmp(
                (string) ($right['code'] ?? ''),
                (string) ($left['code'] ?? ''),
            ),
        );

        return $rules;
    }

    public function usesOfficialInstances(Tournament $tournament): bool
    {
        $tournament->loadMissing('type', 'discipline');

        return (bool) $tournament->type?->affects_ranking
            && (! $tournament->discipline || $tournament->discipline->code === 'five_quillas');
    }

    /** Devuelve el resultado vigente de la posición vinculada a la inscripción. */
    public function performanceScore(TournamentRegistration $registration): array
    {
        $tournament = $registration->tournament;
        $instance = $registration->tournamentInstance;
        $fallbackDescription = $registration->result_description ?? $instance?->description ?? 'Sin resultado';
        if ($registration->disqualified) {
            return ['points' => 0.0, 'description' => $fallbackDescription];
        }

        $code = trim((string) $registration->result_code);
        if (! $tournament || (! $registration->tournament_instance_id && $code === '')) {
            return ['points' => $registration->points, 'description' => $fallbackDescription];
        }

        $rule = $instance
            ? $this->findRuleByTournamentInstance($tournament, $instance)
            : ($code !== '' ? $this->findRuleByCode($tournament, $code) : null);
        if (! $rule) {
            return ['points' => null, 'description' => $fallbackDescription];
        }

        $absent = $registration->status === 'ausente';

        return [
            'points' => $absent ? 0.0 : (float) $rule['points'],
            'description' => ($absent ? 'Ausente · ' : '').(string) $rule['description'],
        ];
    }

    public function synchronizeTypeAssignments(TournamentType $type): void
    {
        $type->loadMissing('discipline');
        $rules = is_array($type->scoring_rules)
            ? array_values($type->scoring_rules)
            : [];
        $rulesByCode = collect($rules)->keyBy(
            fn (array $rule): string => trim((string) ($rule['code'] ?? ''))
        );
        $rulesByInstance = collect($rules)
            ->filter(fn (array $rule): bool => ! empty($rule['tournament_instance_id']))
            ->keyBy(fn (array $rule): int => (int) $rule['tournament_instance_id']);

        DB::transaction(function () use ($type, $rules, $rulesByCode, $rulesByInstance): void {
            $tournamentIds = $type->tournaments()->pluck('id');

            $type->tournaments()->update([
                'scoring_rules' => $rules === [] ? null : json_encode($rules),
            ]);

            $finalRules = [];
            $confirmed = [];
            if ($type->assigns_points && $type->scoring_method === 'position'
                && Schema::hasTable('five_quillas_competitions')) {
                $competitionService = app(FiveQuillasCompetitionService::class);
                $orderedRules = $this->orderedRules($rules);
                FiveQuillasCompetition::query()->whereIn('tournament_id', $tournamentIds)
                    ->where('status', 'completed')
                    ->whereDoesntHave('phases', fn ($query) => $query->where('is_enabled', true)->where('status', '!=', 'completed'))
                    ->lockForUpdate()
                    ->eachById(function (FiveQuillasCompetition $competition) use ($competitionService, $orderedRules, &$finalRules, &$confirmed): void {
                        $assignments = $competitionService->finalScoringRulesByRegistration($competition, $orderedRules);
                        $finalRules += $assignments;
                        if (data_get($competition->settings, 'scoring_confirmed_at')) {
                            $confirmed += $assignments;
                        }
                    });
            }

            $usesOfficialInstances = $type->affects_ranking && (! $type->discipline || $type->discipline->code === 'five_quillas');
            TournamentRegistration::query()
                ->whereIn('tournament_id', $tournamentIds)
                ->where(function ($query) use ($finalRules, $confirmed): void {
                    $query->whereNotNull('tournament_instance_id')
                        ->orWhereNotNull('result_code')
                        ->orWhereIntegerInRaw('id', array_keys($confirmed));
                    if ($finalRules !== []) {
                        $query->orWhere(fn ($assigned) => $assigned->whereNotNull('points')
                            ->whereIntegerInRaw('id', array_keys($finalRules)));
                    }
                })
                ->lockForUpdate()
                ->eachById(function (TournamentRegistration $registration) use ($usesOfficialInstances, $finalRules, $rulesByCode, $rulesByInstance): void {
                    if ($registration->disqualified) {
                        $registration->points = 0;
                        $registration->saveQuietly();

                        return;
                    }

                    $storedRule = $usesOfficialInstances
                        ? $rulesByInstance->get((int) $registration->tournament_instance_id)
                        : null;
                    $storedRule ??= $rulesByCode->get(trim((string) $registration->result_code));
                    $rule = array_key_exists($registration->id, $finalRules)
                        ? $finalRules[$registration->id]
                        : $storedRule;

                    if (! $rule) {
                        $registration->points = null;
                        $registration->result_description = null;
                        $registration->result_instance_value = null;
                        if ($registration->status === 'ausente') {
                            $registration->penalty_points = 0;
                        }
                        $registration->saveQuietly();

                        return;
                    }

                    $sameCode = (string) $registration->result_code === (string) $rule['code'];
                    $registration->tournament_instance_id = $usesOfficialInstances
                        ? ($rule['tournament_instance_id'] ?? ($sameCode ? $registration->tournament_instance_id : null))
                        : null;
                    $registration->result_code = (string) $rule['code'];
                    $registration->result_description = (string) $rule['description'];
                    $registration->result_instance_value = (int) $rule['instance_value'];
                    $registration->points = (float) $rule['points'];
                    if ($registration->status === 'ausente') {
                        $registration->points = 0;
                        $registration->penalty_points = $this->absencePenaltyForRule($registration, $rule);
                        $registration->result_description = 'Ausente · '.(string) $rule['description'];
                    }
                    $registration->saveQuietly();
                });
        });

        if (
            $type->affects_ranking
            && $type->discipline?->code === 'five_quillas'
            && Schema::hasTable('general_rankings')
            && \App\Models\GeneralRanking::query()->exists()
        ) {
            RankingService::syncGeneralRanking();
        }

        app(ThreeCushionRankingService::class)->syncForTournamentType($type);
    }

    /**
     * Busca una regla mediante su código propio.
     *
     * Se utiliza principalmente para tipos estadísticos que no afectan
     * al Ranking General.
     */
    public function findRuleByCode(
        Tournament $tournament,
        string $resultCode
    ): ?array {
        $resultCode = trim($resultCode);

        foreach ($this->getRules($tournament) as $rule) {
            if (
                (string) ($rule['code'] ?? '') === $resultCode
            ) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * Busca una regla vinculada con una posición oficial.
     *
     * Se utiliza para CAB y para cualquier otro tipo que en el futuro
     * afecte al Ranking General.
     */
    public function findRuleByTournamentInstance(
        Tournament $tournament,
        TournamentInstance $instance
    ): ?array {
        $legacyRule = null;
        foreach ($this->getRules($tournament) as $rule) {
            $ruleInstanceId = $rule['tournament_instance_id'] ?? null;

            if (
                $ruleInstanceId !== null
                && (int) $ruleInstanceId === (int) $instance->id
            ) {
                return $rule;
            }

            if (
                empty($ruleInstanceId)
                && (string) ($rule['code'] ?? '') === (string) $instance->code
            ) {
                $legacyRule ??= $rule;
            }
        }

        return $legacyRule;
    }

    /**
     * Asigna un resultado propio del tipo de torneo.
     *
     * Se utiliza para tipos estadísticos con resultados como:
     * CHAMPION, RUNNER_UP, SEMIFINAL, etc.
     */
    public function assignByCode(
        TournamentRegistration $registration,
        string $resultCode
    ): TournamentRegistration {
        $tournament = $this->getTournament($registration);
        $this->validateTournamentType($tournament);

        $rule = $this->findRuleByCode(
            $tournament,
            $resultCode
        );

        if (! $rule) {
            throw ValidationException::withMessages([
                'result_code' => 'El resultado seleccionado no existe en la tabla de puntuación del tipo de torneo.',
            ]);
        }

        return $this->applyRule(
            $registration,
            $rule
        );
    }

    /**
     * Asigna una posición oficial vinculada con tournament_instances.
     *
     * Este método conserva la estructura utilizada actualmente
     * por el Ranking General.
     */
    public function assignByTournamentInstance(
        TournamentRegistration $registration,
        int $tournamentInstanceId
    ): TournamentRegistration {
        $tournament = $this->getTournament($registration);
        $this->validateTournamentType($tournament);

        $instance = TournamentInstance::query()
            ->find($tournamentInstanceId);

        if (! $instance) {
            throw ValidationException::withMessages([
                'tournament_instance_id' => 'La posición oficial seleccionada no existe.',
            ]);
        }

        $rule = $this->findRuleByTournamentInstance(
            $tournament,
            $instance
        );

        if (! $rule) {
            throw ValidationException::withMessages([
                'tournament_instance_id' => 'La posición seleccionada no tiene una puntuación configurada para este tipo de torneo.',
            ]);
        }

        return $this->applyRule(
            $registration,
            $rule,
            $instance
        );
    }

    /**
     * Obtiene el torneo de la inscripción.
     */
    private function getTournament(
        TournamentRegistration $registration
    ): Tournament {
        $registration->loadMissing('tournament.type');

        $tournament = $registration->tournament;

        if (! $tournament) {
            throw ValidationException::withMessages([
                'tournament_id' => 'La inscripción no tiene un torneo válido.',
            ]);
        }

        return $tournament;
    }

    /**
     * Verifica que el tipo pueda calcular puntos.
     */
    private function validateTournamentType(
        Tournament $tournament
    ): void {
        $type = $tournament->type;

        if (! $type) {
            throw ValidationException::withMessages([
                'tournament_type_id' => 'El torneo no tiene un tipo configurado.',
            ]);
        }

        if (! $type->assigns_points) {
            throw ValidationException::withMessages([
                'tournament_type_id' => 'Este tipo de torneo no asigna puntos.',
            ]);
        }

        if ($type->scoring_method !== 'position') {
            throw ValidationException::withMessages([
                'scoring_method' => 'El tipo de torneo no utiliza puntuación por posición.',
            ]);
        }

        if ($this->getRules($tournament) === []) {
            throw ValidationException::withMessages([
                'scoring_rules' => 'El tipo de torneo no tiene una tabla de puntuación configurada.',
            ]);
        }
    }

    /**
     * Guarda una copia histórica del resultado y sus puntos.
     */
    public function absencePenaltyForRule(TournamentRegistration $registration, array $rule): float
    {
        $entry = \App\Models\FiveQuillasEntry::query()->where('tournament_registration_id', $registration->id)
            ->with('entryPhase')->first();
        $label = $entry?->entryPhase
            ? $entry->entryPhase->code.' '.$entry->entryPhase->name
            : (string) $registration->slot?->name;

        $label = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($label.' '.($rule['code'] ?? '').' '.($rule['description'] ?? '')));

        return (\Illuminate\Support\Str::contains($label, ['nacional', 'national', 'master']) || preg_match('/\bf[12]\s*nac\b/', $label))
            ? (float) $rule['points'] : 0.0;
    }

    private function applyRule(
        TournamentRegistration $registration,
        array $rule,
        ?TournamentInstance $instance = null
    ): TournamentRegistration {
        if (
            ! array_key_exists('code', $rule)
            || ! array_key_exists('description', $rule)
            || ! array_key_exists('instance_value', $rule)
            || ! array_key_exists('points', $rule)
        ) {
            throw ValidationException::withMessages([
                'scoring_rules' => 'La regla de puntuación seleccionada está incompleta.',
            ]);
        }

        return DB::transaction(function () use (
            $registration,
            $rule,
            $instance
        ): TournamentRegistration {
            $registration->result_code =
                (string) $rule['code'];

            $registration->result_description =
                (string) $rule['description'];

            $registration->result_instance_value =
                (int) $rule['instance_value'];

            $registration->points =
                (float) $rule['points'];
            if ($registration->status === 'ausente') {
                $registration->points = 0;
                $registration->penalty_points = $this->absencePenaltyForRule($registration, $rule);
                $registration->result_description = 'Ausente · '.(string) $rule['description'];
            }

            /*
         * Los torneos de ranking conservan la relación con
         * tournament_instances.
         */
            $registration->tournament_instance_id = $instance?->id
                ?? ($this->usesOfficialInstances($registration->tournament)
                    ? ($rule['tournament_instance_id'] ?? null)
                    : null);

            $registration->save();

            if (Schema::hasTable('three_cushion_stage_results')) {
                $stageResult = $registration->threeCushionStageResult;

                if ($stageResult) {
                    app(ThreeCushionRankingService::class)->syncForResult($stageResult);
                }
            }

            return $registration->refresh();
        });
    }
}
