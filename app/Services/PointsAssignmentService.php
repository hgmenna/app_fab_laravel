<?php

namespace App\Services;

use App\Models\TournamentRegistration;

class PointsAssignmentService
{
    public function __construct(
        private readonly TournamentScoringService $scoringService
    ) {}

    public function assignPoints(
        TournamentRegistration $registration
    ): void {
        /*
         * Se conserva el comportamiento existente para jugadores
         * descalificados, pero se utiliza el campo oficial points.
         */
        if ($registration->disqualified) {
            $registration->points = 0;
            $registration->save();

            return;
        }

        $registration->loadMissing('tournament.type');

        /*
         * Las posiciones oficiales pertenecen al ranking general de 5 Quillas.
         * Las demás disciplinas usan la tabla de puntuación de su tipo de torneo,
         * incluso cuando ese torneo afecta a su ranking propio.
         */
        if ($registration->tournament && $this->scoringService->usesOfficialInstances($registration->tournament)) {
            if (! $registration->tournament_instance_id) {
                $registration->points = null;
                $registration->save();

                return;
            }

            $this->scoringService->assignByTournamentInstance(
                $registration,
                (int) $registration->tournament_instance_id
            );

            return;
        }

        /*
         * Torneos estadísticos:
         * utilizan el código propio definido en su array.
         */
        if (! $registration->result_code) {
            $registration->points = null;
            $registration->save();

            return;
        }

        $this->scoringService->assignByCode(
            $registration,
            (string) $registration->result_code
        );
    }
}
