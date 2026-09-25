<?php

namespace App\Services;

use App\Exceptions\TournamentRegulationBlockedException;
use App\Models\Tournament;
use App\Models\TournamentRegulationAudit;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TournamentRegulationWorkflow
{
    public function __construct(private readonly TournamentRegulationService $regulations) {}

    public function validate(array $data, User $user, string $operation, ?Tournament $record = null): array
    {
        $data['manual_route_checks'] = collect($data['manual_route_checks'] ?? [])
            ->map(function (array $check) use ($user): array {
                $check['checked_by'] = $user->id;
                $check['checked_at'] = now()->toIso8601String();

                return $check;
            })
            ->values()
            ->all();

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

            throw new TournamentRegulationBlockedException($evaluation, $isSuperAdmin);
        }

        if ($overrideReason === '') {
            $this->audit($record, $user, $operation, 'blocked', false, null, $data, $evaluation);

            throw new TournamentRegulationBlockedException(
                $evaluation,
                true,
                'El motivo de fuerza mayor es obligatorio para autorizar la excepción.',
            );
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
        [$snapshot, $conflicts, $technicalDetails] = $this->preserveEvidence(
            $this->snapshot($snapshot),
            $evaluation->conflicts,
            $evaluation->technicalDetails,
        );

        return TournamentRegulationAudit::create([
            'tournament_id' => $tournament?->id,
            'user_id' => $user->id,
            'operation' => $operation,
            'result' => $result,
            'overridden' => $overridden,
            'override_reason' => $overrideReason,
            'tournament_snapshot' => $snapshot,
            'conflicts' => $conflicts,
            'technical_details' => $technicalDetails,
        ]);
    }

    private function preserveEvidence(array $snapshot, array $conflicts, array $technicalDetails): array
    {
        $paths = collect();
        $this->collectEvidencePaths($snapshot, $paths);
        $this->collectEvidencePaths($conflicts, $paths);
        $this->collectEvidencePaths($technicalDetails, $paths);

        $disk = Storage::disk('public_path');
        $folder = 'tournament-regulation-audits/'.Str::uuid();
        $pathMap = [];

        foreach ($paths->filter()->unique()->values() as $index => $sourcePath) {
            if (! is_string($sourcePath) || ! $disk->exists($sourcePath)) {
                continue;
            }

            $targetPath = $folder.'/'.($index + 1).'-'.basename($sourcePath);

            if ($disk->copy($sourcePath, $targetPath)) {
                $pathMap[$sourcePath] = $targetPath;
            }
        }

        return [
            $this->replaceEvidencePaths($snapshot, $pathMap),
            $this->replaceEvidencePaths($conflicts, $pathMap),
            $this->replaceEvidencePaths($technicalDetails, $pathMap),
        ];
    }

    private function collectEvidencePaths(array $data, $paths): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'evidence_path' && is_string($value) && $value !== '') {
                $paths->push($value);
            }

            if (is_array($value)) {
                $this->collectEvidencePaths($value, $paths);
            }
        }
    }

    private function replaceEvidencePaths(array $data, array $pathMap): array
    {
        foreach ($data as $key => $value) {
            if ($key === 'evidence_path' && is_string($value) && isset($pathMap[$value])) {
                $data[$key] = $pathMap[$value];
            } elseif (is_array($value)) {
                $data[$key] = $this->replaceEvidencePaths($value, $pathMap);
            }
        }

        return $data;
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
