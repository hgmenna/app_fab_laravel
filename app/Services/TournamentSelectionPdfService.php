<?php

namespace App\Services;

use App\Models\Category;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/** Genera el listado PDF de los torneos seleccionados con sus datos deportivos y fechas. */
class TournamentSelectionPdfService
{
    /** Carga los datos del listado en bloque y ordena los torneos por fecha y nombre. */
    public static function pdf(Collection $records)
    {
        abort_if($records->isEmpty(), 422, 'Seleccioná al menos un torneo.');
        $tournaments = new \Illuminate\Database\Eloquent\Collection($records->all());
        $tournaments->loadMissing([
            'discipline', 'type', 'category', 'venue', 'tournamentModalities',
        ]);
        $categoryIds = $tournaments->flatMap(fn ($tournament) => collect($tournament->categories ?? [])
            ->merge($tournament->tournamentModalities->flatMap(fn ($modality) => $modality->categories ?? [])))
            ->filter()->unique();

        return Pdf::loadView('pdf.selected-tournaments', [
            'tournaments' => $tournaments->sortBy(fn ($tournament) => [
                $tournament->start_date?->format('Y-m-d') ?? '', $tournament->name,
            ])->values(),
            'categories' => Category::query()->whereIn('id', $categoryIds)->pluck('name', 'id'),
            'generatedAt' => now(config('cliente.zona_horaria', 'America/Argentina/Buenos_Aires')),
        ])->setPaper('a4', 'landscape');
    }
}
