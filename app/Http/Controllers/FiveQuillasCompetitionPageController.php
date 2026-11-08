<?php

namespace App\Http\Controllers;

use App\Helpers\CompetitionViewVersion;
use App\Models\FiveQuillasCompetition;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Renderiza la página pública de una competencia de cinco quillas.
 */
class FiveQuillasCompetitionPageController extends Controller
{
    /**
     * Atiende la solicitud después de resolver autorización y datos publicados.
     */
    public function __invoke(string $slug): Response
    {
        $competition = FiveQuillasCompetition::query()
            ->where('public_slug', $slug)
            ->with([
                'tournament.type.publicationFederation',
                'tournament.venue.city.state.federation',
                'tournament.externalVenueState.federation',
                'tournament.tournamentModalities.modality',
            ])->firstOrFail();

        if (! $competition->is_public) {
            return response()->view('public.five-quillas-competition-unavailable', [
                'competition' => $competition,
            ])->header('Cache-Control', 'no-store');
        }

        $tournament = $competition->tournament;
        $logoPath = $tournament->publicationLogoPath();

        return response()->view('public.five-quillas-competition', [
            'competition' => $competition,
            'viewVersion' => CompetitionViewVersion::current(),
            'jsonUrl' => route('five-quillas-competition.public', ['slug' => $slug]),
            'pdfBaseUrl' => url('/torneos/5-quillas/'.$slug.'/secciones'),
            'tournamentInfo' => [
                'name' => $tournament->name,
                'venue' => $tournament->venueName(),
                'date' => $tournament->start_date
                    ? ($tournament->end_date && ! $tournament->end_date->isSameDay($tournament->start_date)
                        ? $tournament->start_date->format(config('cliente.formato_fecha')).' al '.$tournament->end_date->format(config('cliente.formato_fecha'))
                        : $tournament->start_date->format(config('cliente.formato_fecha')))
                    : 'Sin fecha',
                'type' => $tournament->type?->name ?? 'Sin tipo',
                'official' => $tournament->type?->is_official ? 'Oficial' : 'No oficial',
                'modality' => $tournament->tournamentModalities->pluck('modality.name')->filter()->implode(', ') ?: 'Sin modalidad',
                'logo' => $logoPath ? Storage::disk('public_path')->url($logoPath) : null,
            ],
        ])->header('Cache-Control', 'no-store');
    }
}
