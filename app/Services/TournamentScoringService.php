<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentInstance;
use App\Models\TournamentRegistration;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class TournamentScoringService
{
    /**
     * Obtiene las reglas aplicables al torneo.
     *
     * Primero utiliza la copia guardada en el torneo. Si el torneo todavía
     * no posee una copia, utiliza las reglas actuales de su tipo.
     */
    public function getRules(Tournament $tournament): array
    {
        $tournament->loadMissing('type');

        $tournamentRules = $tournament->scoring_rules;

        if (is_array($tournamentRules) && $tournamentRules !== []) {
            return array_values($tournamentRules);
        }

        $typeRules = $tournament->type?->scoring_rules;

        if (! is_array($typeRules)) {
            return [];
        }

        return array_values($typeRules);
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
                'result_code' =>
                'El resultado seleccionado no existe en la tabla de puntuación del tipo de torneo.',
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
                'tournament_instance_id' =>
                'La posición oficial seleccionada no existe.',
            ]);
        }

        $rule = $this->findRuleByTournamentInstance(
            $tournament,
            $instance
        );

        if (! $rule) {
            throw ValidationException::withMessages([
                'tournament_instance_id' =>
                'La posición seleccionada no tiene una puntuación configurada para este tipo de torneo.',
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
                'tournament_id' =>
                'La inscripción no tiene un torneo válido.',
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
                'tournament_type_id' =>
                'El torneo no tiene un tipo configurado.',
            ]);
        }

        if (! $type->assigns_points) {
            throw ValidationException::withMessages([
                'tournament_type_id' =>
                'Este tipo de torneo no asigna puntos.',
            ]);
        }

        if ($type->scoring_method !== 'position') {
            throw ValidationException::withMessages([
                'scoring_method' =>
                'El tipo de torneo no utiliza puntuación por posición.',
            ]);
        }

        if ($this->getRules($tournament) === []) {
            throw ValidationException::withMessages([
                'scoring_rules' =>
                'El tipo de torneo no tiene una tabla de puntuación configurada.',
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
                'scoring_rules' =>
                'La regla de puntuación seleccionada está incompleta.',
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
        if (
            is_array($tournament->scoring_rules)
            && $tournament->scoring_rules !== []
        ) {
            return;
        }

        $tournament->loadMissing('type');

        $typeRules = $tournament->type?->scoring_rules;

        if (! is_array($typeRules) || $typeRules === []) {
            throw ValidationException::withMessages([
                'scoring_rules' =>
                'No se puede iniciar el torneo porque su tipo no tiene una tabla de puntuación configurada.',
            ]);
        }

        $tournament->scoring_rules =
            array_values($typeRules);

        /*
     * saveQuietly evita ejecutar observadores o acciones secundarias
     * que no corresponden al guardar la fotografía.
     */
        $tournament->saveQuietly();
    }
}
