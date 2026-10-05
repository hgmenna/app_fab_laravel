<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentInstance;
use App\Models\TournamentRegistration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        $affectsRanking = (bool) $tournament->type?->affects_ranking;

        if ($affectsRanking && ! $tournamentInstanceId) {
            throw ValidationException::withMessages([
                'tournament_instance_id' => 'Seleccioná una posición oficial.',
            ]);
        }

        if (! $affectsRanking && blank($resultCode)) {
            throw ValidationException::withMessages([
                'result_code' => 'Seleccioná un resultado.',
            ]);
        }

        return DB::transaction(function () use (
            $tournament,
            $items,
            $registrationIds,
            $affectsRanking,
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
                    $affectsRanking
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
     * Conserva la copia guardada en el torneo para las posiciones que ya
     * existían y agrega las posiciones nuevas incorporadas posteriormente
     * al tipo. Los valores históricos nunca se sobrescriben.
     */
    public function getRules(Tournament $tournament): array
    {
        $tournament->loadMissing('type');

        $tournamentRules = $tournament->scoring_rules;

        $typeRules = $tournament->type?->scoring_rules;

        if (! is_array($tournamentRules) || $tournamentRules === []) {
            return is_array($typeRules) ? array_values($typeRules) : [];
        }

        if (! is_array($typeRules) || $typeRules === []) {
            return array_values($tournamentRules);
        }

        $rules = collect($tournamentRules)
            ->keyBy(fn (array $rule): string => $this->ruleIdentity($rule));

        foreach ($typeRules as $rule) {
            $identity = $this->ruleIdentity($rule);

            if (! $rules->has($identity)) {
                $rules->put($identity, $rule);
            }
        }

        return $rules->values()->all();
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
        foreach ($this->getRules($tournament) as $rule) {
            $ruleInstanceId = $rule['tournament_instance_id'] ?? null;

            if (
                $ruleInstanceId !== null
                && (int) $ruleInstanceId === (int) $instance->id
            ) {
                return $rule;
            }

            if (
                (string) ($rule['code'] ?? '')
                === (string) $instance->code
            ) {
                return $rule;
            }
        }

        return null;
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
            $tournament = $registration->tournament;

            if ($tournament) {
                $this->persistRulesSnapshot($tournament);
            }

            $registration->result_code =
                (string) $rule['code'];

            $registration->result_description =
                (string) $rule['description'];

            $registration->result_instance_value =
                (int) $rule['instance_value'];

            $registration->points =
                (float) $rule['points'];

            /*
         * Los torneos de ranking conservan la relación con
         * tournament_instances.
         */
            if ($instance) {
                $registration->tournament_instance_id =
                    $instance->id;
            } elseif (
                ! $registration->tournament?->type?->affects_ranking
            ) {
                $registration->tournament_instance_id = null;
            }

            $registration->save();

            return $registration->refresh();
        });
    }

    /**
     * Guarda una fotografía de las reglas en el torneo cuando se asigna
     * su primer resultado.
     *
     * Una vez creada, la fotografía no vuelve a sobrescribirse
     * automáticamente.
     */
    private function persistRulesSnapshot(
        Tournament $tournament
    ): void {
        $rules = $this->getRules($tournament);

        if ($rules === []) {
            throw ValidationException::withMessages([
                'scoring_rules' => 'No se puede iniciar el torneo porque su tipo no tiene una tabla de puntuación configurada.',
            ]);
        }

        if ($tournament->scoring_rules === $rules) {
            return;
        }

        $tournament->scoring_rules = $rules;

        /*
     * saveQuietly evita ejecutar observadores o acciones secundarias
     * que no corresponden al guardar la fotografía.
     */
        $tournament->saveQuietly();
    }

    private function ruleIdentity(array $rule): string
    {
        if (! empty($rule['tournament_instance_id'])) {
            return 'instance:'.(int) $rule['tournament_instance_id'];
        }

        return 'code:'.trim((string) ($rule['code'] ?? ''));
    }
}
