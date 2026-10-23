<?php

namespace App\Http\Controllers;

use App\Models\FiveQuillasCompetition;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Renderiza la página pública de una competencia de cinco quillas.
 */
class FiveQuillasCompetitionPageController extends Controller
{
    /**
     * Atiende la solicitud después de resolver autorización y datos publicados.
     */
    public function __invoke(string $slug): View
    {
        $competition = FiveQuillasCompetition::query()
            ->where('public_slug', $slug)->where('is_public', true)
            ->with([
                'tournament.type.publicationFederation',
                'tournament.venue.city.state.federation',
                'tournament.externalVenueState.federation',
                'tournament.tournamentModalities.modality',
            ])->firstOrFail();

        $tournament = $competition->tournament;
        $logoPath = $tournament->publicationLogoPath();

        return view('public.five-quillas-competition', [
            'competition' => $competition,
            'jsonUrl' => route('five-quillas-competition.public', ['slug' => $slug]),
            'pdfBaseUrl' => url('/torneos/5-quillas/'.$slug.'/secciones'),
            'tournamentInfo' => [
                'name' => $tournament->name,
                'venue' => $tournament->venueName(),
                'date' => $tournament->start_date
                    ? ($tournament->end_date && ! $tournament->end_date->isSameDay($tournament->start_date)
                        ? $tournament->start_date->format('d/m/Y').' al '.$tournament->end_date->format('d/m/Y')
                        : $tournament->start_date->format('d/m/Y'))
                    : 'Sin fecha',
                'type' => $tournament->type?->name ?? 'Sin tipo',
                'official' => $tournament->type?->is_official ? 'Oficial' : 'No oficial',
                'modality' => $tournament->tournamentModalities->pluck('modality.name')->filter()->implode(', ') ?: 'Sin modalidad',
                'logo' => $logoPath ? Storage::disk('public_path')->url($logoPath) : null,
            ],
        ]);
    }
}
