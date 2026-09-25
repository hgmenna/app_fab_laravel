<?php

namespace App\Services;

use App\Helpers\FabPath;
use App\Models\Category;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\TournamentRegulationAudit;
use App\Models\TournamentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TournamentRegulationAuditPdfService
{
    public function download(TournamentRegulationAudit $audit): StreamedResponse
    {
        $audit->loadMissing(['tournament.discipline', 'tournament.type', 'tournament.venue', 'user']);
        $snapshot = $audit->tournament_snapshot ?? [];

        $discipline = $audit->tournament?->discipline
            ?? Discipline::find(data_get($snapshot, 'discipline_id'));
        $type = $audit->tournament?->type
            ?? TournamentType::find(data_get($snapshot, 'tournament_type_id'));
        $club = $audit->tournament?->venue
            ?? Club::find(data_get($snapshot, 'venue_id'));
        $categoryIds = $this->arrayValue(data_get($snapshot, 'categories', []));
        $manualRouteChecks = $this->arrayValue(data_get($snapshot, 'manual_route_checks', []));
        $categoryNames = Category::query()
            ->whereKey($categoryIds)
            ->pluck('name')
            ->all();

        $evidencePaths = collect(data_get($audit->technical_details, 'distance_checks', []))
            ->pluck('evidence_path')
            ->merge(collect($audit->conflicts ?? [])->pluck('route.evidence_path'))
            ->filter()
            ->unique()
            ->values();

        if ($evidencePaths->isEmpty()) {
            $evidencePaths = collect($manualRouteChecks)->pluck('evidence_path')->filter()->unique()->values();
        }

        $evidence = $evidencePaths->map(function (string $path): array {
            $absolutePath = Storage::disk('public_path')->path($path);
            $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

            return [
                'path' => $path,
                'absolute_path' => $absolutePath,
                'exists' => is_file($absolutePath),
                'is_image' => is_file($absolutePath) && in_array($extension, ['jpg', 'jpeg', 'png'], true),
            ];
        })->all();

        $pdf = Pdf::loadView('pdf.tournament-regulation-audit', [
            'audit' => $audit,
            'snapshot' => $snapshot,
            'discipline' => $discipline,
            'type' => $type,
            'club' => $club,
            'categoryNames' => $categoryNames,
            'evidence' => $evidence,
            'logo' => is_file(FabPath::logo()) ? FabPath::logo() : null,
            'footer_image' => is_file(FabPath::footer()) ? FabPath::footer() : null,
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4');

        $fileName = 'auditoria-reglamentaria-'.$audit->id.'.pdf';

        return response()->streamDownload(
            static fn () => print $pdf->output(),
            $fileName,
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
