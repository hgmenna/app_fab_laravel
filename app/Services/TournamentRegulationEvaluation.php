<?php

namespace App\Services;

class TournamentRegulationEvaluation
{
    public function __construct(
        public readonly array $conflicts,
        public readonly array $technicalDetails = [],
    ) {}

    public function passes(): bool
    {
        return $this->conflicts === [];
    }

    public function message(): string
    {
        if ($this->passes()) {
            return 'El torneo cumple todas las reglas configuradas.';
        }

        return collect($this->conflicts)
            ->map(fn (array $conflict): string => $conflict['message'])
            ->implode("\n");
    }
}
