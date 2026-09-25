<?php

namespace App\Exceptions;

use App\Services\TournamentRegulationEvaluation;
use RuntimeException;

class TournamentRegulationBlockedException extends RuntimeException
{
    public function __construct(
        public readonly TournamentRegulationEvaluation $evaluation,
        public readonly bool $isSuperAdmin,
        string $message = 'El torneo incumple una o más reglas reglamentarias.',
    ) {
        parent::__construct($message);
    }
}
