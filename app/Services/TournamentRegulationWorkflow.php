<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentRegulationAudit;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TournamentRegulationWorkflow
{
    public function __construct(private readonly TournamentRegulationService $regulations) {}

    public function validate(array $data, User $user, string $operation, ?Tournament $record = null): array
    {
        $candidate = $record ? $record->replicate() : new Tournament;
        $candidate->forceFill($data);
        $candidate->exists = (bool) $record;

        if ($record) {
            $candidate->setAttribute('id', $record->id);
        }

        $evaluation = $this->regulations->evaluate($candidate);
        $isSuperAdmin = $user->hasRole('super-admin');
        $overrideRequested = (bool) ($data['regulatory_override'] ?? false);
        $overrideReason = trim((string) ($data['regulatory_override_reason'] ?? ''));

        if ($evaluation->passes()) {
            $data['regulatory_override'] = false;
            $data['regulatory_override_reason'] = null;
            $data['regulatory_override_by'] = null;
            $data['regulatory_override_at'] = null;

            return [$data, $evaluation, false];
        }

        if (! $isSuperAdmin || ! $overrideRequested) {
            $this->audit($record, $user, $operation, 'blocked', false, null, $data, $evaluation);

            throw ValidationException::withMessages([
                $isSuperAdmin ? 'regulatory_override' : 'name' => $evaluation->message(),
            ]);
        }

        if ($overrideReason === '') {
            $this->audit($record, $user, $operation, 'blocked', false, null, $data, $evaluation);

            throw ValidationException::withMessages([
                'regulatory_override_reason' => 'El motivo de fuerza mayor es obligatorio para autorizar la excepción.',
            ]);
        }

        $data['regulatory_override'] = true;
        $data['regulatory_override_reason'] = $overrideReason;
        $data['regulatory_override_by'] = $user->id;
        $data['regulatory_override_at'] = now();

        return [$data, $evaluation, true];
    }

    public function audit(
        ?Tournament $tournament,
        User $user,
        string $operation,
        string $result,
        bool $overridden,
        ?string $overrideReason,
        array $snapshot,
        TournamentRegulationEvaluation $evaluation,
    ): TournamentRegulationAudit {
        return TournamentRegulationAudit::create([
            'tournament_id' => $tournament?->id,
            'user_id' => $user->id,
            'operation' => $operation,
            'result' => $result,
            'overridden' => $overridden,
            'override_reason' => $overrideReason,
            'tournament_snapshot' => $this->snapshot($snapshot),
            'conflicts' => $evaluation->conflicts,
            'technical_details' => $evaluation->technicalDetails,
        ]);
    }

    private function snapshot(array $data): array
    {
        return collect($data)->except([
            'categoryPrices',
            'slots',
            'scoring_rules',
        ])->all();
    }
}
