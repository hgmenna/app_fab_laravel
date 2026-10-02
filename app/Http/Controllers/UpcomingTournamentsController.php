<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Category;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UpcomingTournamentsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $monthNames = [
            1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO',
            4 => 'ABRIL', 5 => 'MAYO', 6 => 'JUNIO',
            7 => 'JULIO', 8 => 'AGOSTO', 9 => 'SEPTIEMBRE',
            10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
        ];

        $categoriesByDiscipline = Category::query()
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'discipline_id', 'name'])
            ->groupBy('discipline_id');

        $rows = Tournament::query()
            ->with([
                'discipline:id,name',
                'type:id,name,has_handicap,is_official,publication_logo_source,publication_federation_id',
                'type.publicationFederation:id,name,logo_path',
                'venue:id,name,logo_path,address,lat,lng,city_id',
                'venue.city:id,state_id',
                'venue.city.state:id,federation_id',
                'venue.city.state.federation:id,name,short_name,logo_path',
                'tournamentModalities.modality:id,name,players_per_registration',
                'registrations:id,tournament_id,tournament_modality_id',
                'registrations.participants:id,tournament_registration_id,player_id',
            ])
            ->withCount('registrations')
            ->whereDate('end_date', '>=', today('America/Argentina/Buenos_Aires'))
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->map(function (Tournament $tournament) use ($monthNames, $categoriesByDiscipline): array {
                $club = $tournament->venue;

                $disciplineCategories = $categoriesByDiscipline
                    ->get($tournament->discipline_id, collect());
                $selectedCategoryIds = collect($tournament->categories ?? [])
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values();
                $allCategoryIds = $disciplineCategories
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->values();
                $includesEveryCategory = $allCategoryIds->isNotEmpty()
                    && $selectedCategoryIds->sort()->values()->all() === $allCategoryIds->sort()->values()->all();
                $categories = $includesEveryCategory
                    ? 'Todas'
                    : $selectedCategoryIds
                        ->map(fn (int $id) => $disciplineCategories->firstWhere('id', $id)?->name)
                        ->filter()
                        ->implode(', ');

                $mapUrl = null;

                if ($club?->lat !== null && $club?->lng !== null) {
                    $mapUrl = 'https://www.google.com/maps/search/?api=1&query='
                        .$club->lat.','.$club->lng;
                } elseif ($club?->address) {
                    $mapUrl = 'https://www.google.com/maps/search/?api=1&query='
                        .rawurlencode($club->address.', '.$club->name);
                }

                $registrationUrl = TournamentResource::getUrl(
                    'registrations',
                    ['record' => $tournament->id],
                    panel: 'guest',
                );
                $registrationIsOpen = $tournament->isRegistrationOpen();
                $registrationCount = $tournament->registrations->count();
                $participantCount = $tournament->registrations->sum(
                    fn (TournamentRegistration $registration): int => $registration->participants->count()
                );
                $modalities = $tournament->tournamentModalities->pluck('modality.name')->filter()->implode(', ');
                $flyerUrl = $tournament->flyer_path
                    ? Storage::disk('public_path')->url($tournament->flyer_path)
                    : '';
                $publicationLogoPath = $tournament->publicationLogoPath();
                $publicationLogoUrl = $publicationLogoPath
                    ? Storage::disk('public_path')->url($publicationLogoPath)
                    : '';

                $start = $tournament->start_date;
                $end = $tournament->end_date;
                $dayLabel = $start->format('j');

                if ($end && ! $end->isSameDay($start)) {
                    if ($start->format('Y-m') === $end->format('Y-m')) {
                        $dayLabel .= '–'.$end->format('j');
                    } else {
                        $startYear = $start->year !== $end->year ? ' '.$start->year : '';
                        $endYear = $start->year !== $end->year ? ' '.$end->year : '';

                        $dayLabel = $start->format('j').' '
                            .substr($monthNames[$start->month], 0, 3).$startYear
                            .' – '.$end->format('j').' '
                            .substr($monthNames[$end->month], 0, 3).$endYear;
                    }
                }

                return [
                    'mes' => $monthNames[$start->month].' '.$start->year,
                    'fecha' => $dayLabel,
                    'torneo' => $tournament->name,
                    'disciplina' => $tournament->discipline?->name ?? '',
                    'categorias' => $categories,
                    'club' => $club?->name ?? 'SIN ASIGNAR',
                    'tipo' => $tournament->type?->name ?? '',
                    'oficial' => (bool) $tournament->type?->is_official,
                    'provincia' => $club?->city?->state?->federation?->short_name ?? '',
                    'modalidad' => $modalities,
                    'handicap' => $tournament->type?->has_handicap ? 'Sí' : 'No',
                    'inscriptos' => $registrationCount,
                    'inscripciones' => $registrationCount,
                    'participantes' => $participantCount,
                    'unidad_inscripcion' => 'Inscripciones por modalidad',
                    'estado_inscripcion' => $registrationIsOpen ? 'Abierta' : 'Cerrada',
                    'apertura_inscripcion' => $tournament->registration_enabled
                        ? $tournament->registration_open_at?->format('d/m/Y') ?? ''
                        : '',
                    'cierre_inscripcion' => $tournament->registration_enabled
                        ? $tournament->registration_close_at?->format('d/m/Y') ?? ''
                        : '',
                    'inscripcion' => $registrationIsOpen
                        ? $registrationUrl.'||Anotarse'
                        : '',
                    'ubicacion' => $mapUrl ? $mapUrl.'||Mapa' : '',
                    'flyer' => $flyerUrl ? $flyerUrl.'||'.$flyerUrl : '',
                    'flyer_url' => $flyerUrl,
                    'logo_publicacion' => $publicationLogoUrl
                        ? $publicationLogoUrl.'||'.$publicationLogoUrl
                        : '',
                    'logo_publicacion_url' => $publicationLogoUrl,
                ];
            });

        return response()
            ->json(['data' => $rows])
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, s-maxage=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Access-Control-Allow-Origin' => '*',
            ]);
    }
}
