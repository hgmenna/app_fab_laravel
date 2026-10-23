<?php

namespace App\Services;

/**
 * Objeto inmutable con el resultado y los conflictos de una evaluación reglamentaria.
 */
class TournamentRegulationEvaluation
{
    /**
     * Inyecta las dependencias necesarias sin ejecutar operaciones de dominio.
     */
    public function __construct(
        public readonly array $conflicts,
        public readonly array $technicalDetails = [],
    ) {}

    /**
     * Indica si la evaluación terminó sin conflictos reglamentarios.
     */
    public function passes(): bool
    {
        return $this->conflicts === [];
    }

    /**
     * Resume los conflictos en un mensaje adecuado para la interfaz.
     */
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
