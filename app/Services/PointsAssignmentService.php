<?php

namespace App\Services;

use App\Models\TournamentRegistration;

class PointsAssignmentService
{
    public function assignPoints(TournamentRegistration $reg): void
    {
        if ($reg->disqualified) {
            $reg->points_awarded = 0;
            $reg->save();
            return;
        }

        $instance = $reg->instance;
        if (!$instance) {
            $reg->points_awarded = 0;
            $reg->save();
            return;
        }

        $basePoints = $instance->points;
        $multiplier = $reg->tournament->type->score_percentage / 100;

        $reg->points_awarded = $basePoints * $multiplier;
        $reg->save();
    }
}
