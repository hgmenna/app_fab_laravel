<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Category;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Publica torneos próximos y sus datos visibles.
 */
class UpcomingTournamentsController extends Controller
{
    /**
     * Atiende la solicitud después de resolver autorización y datos publicados.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'estado' => ['nullable', 'in:vigentes,finalizados,todos'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'anio' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'disciplina' => ['nullable', 'integer', 'min:1'],
            'tipo' => ['nullable', 'integer', 'min:1'],
            'categoria' => ['nullable', 'integer', 'min:1'],
            'buscar' => ['nullable', 'string', 'max:120'],
        ]);

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

        $today = today('America/Argentina/Buenos_Aires');
        $view = strtolower((string) ($filters['estado'] ?? 'vigentes'));

        $query = Tournament::query()
            ->with([
                'discipline:id,name',
                'type:id,name,has_handicap,is_official,publication_logo_source,publication_federation_id',
                'type.publicationFederation:id,name,logo_path',
                'venue:id,name,logo_path,address,lat,lng,city_id',
                'venue.city:id,state_id',
                'venue.city.state:id,federation_id',
                'venue.city.state.federation:id,name,short_name,logo_path',
                'externalVenueState:id,name,country_id,federation_id',
                'externalVenueState.country:id,name',
                'externalVenueState.federation:id,name,short_name,logo_path',
                'tournamentModalities.modality:id,name,players_per_registration',
                'contacts:id,tournament_id,name,phone,sort_order',
                'fiveQuillasCompetition:id,tournament_id,public_slug,status,is_public',
                'registrations:id,tournament_id,tournament_modality_id',
                'registrations.participants:id,tournament_registration_id,player_id',
            ])
            ->withCount('registrations');

        match ($view) {
            'finalizados' => $query->whereDate('end_date', '<', $today),
            'todos' => $query,
            default => $query->whereDate('end_date', '>=', $today),
        };

        $query
            ->when(isset($filters['desde']), fn ($builder) => $builder->whereDate('end_date', '>=', $filters['desde']))
            ->when(isset($filters['hasta']), fn ($builder) => $builder->whereDate('end_date', '<=', $filters['hasta']))
            ->when(isset($filters['anio']), fn ($builder) => $builder->whereYear('end_date', (int) $filters['anio']))
            ->when(isset($filters['disciplina']), fn ($builder) => $builder->where('discipline_id', (int) $filters['disciplina']))
            ->when(isset($filters['tipo']), fn ($builder) => $builder->where('tournament_type_id', (int) $filters['tipo']))
            ->when(isset($filters['categoria']), function ($builder) use ($filters): void {
                $categoryId = (int) $filters['categoria'];
                $builder->where(function ($categoryQuery) use ($categoryId): void {
                    $categoryQuery->where('category_id', $categoryId)
                        ->orWhereJsonContains('categories', $categoryId)
                        ->orWhereJsonContains('categories', (string) $categoryId);
                });
            })
            ->when(isset($filters['buscar']), function ($builder) use ($filters): void {
                $search = trim((string) $filters['buscar']);
                $builder->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('external_venue_name', 'like', "%{$search}%")
                        ->orWhereHas('venue', fn ($venue) => $venue->where('name', 'like', "%{$search}%"));
                });
            });

        $rows = $query
            ->when(
                $view === 'finalizados',
                fn ($builder) => $builder->orderByDesc('end_date')->orderByDesc('id'),
                fn ($builder) => $builder->orderBy('start_date')->orderBy('id'),
            )
            ->get()
            ->map(function (Tournament $tournament) use ($monthNames, $categoriesByDiscipline, $today): array {
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

                if ($tournament->venue_type === 'external' && $tournament->hasCompleteVenueAddress()) {
                    $mapUrl = 'https://www.google.com/maps/search/?api=1&query='
                        .rawurlencode($tournament->venueName().', '.$tournament->venueAddress());
                } elseif ($club?->lat !== null && $club?->lng !== null) {
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
                $competition = $tournament->fiveQuillasCompetition;
                $competitionJsonUrl = $competition?->is_public
                    ? route('five-quillas-competition.public', ['slug' => $competition->public_slug])
                    : '';
                $competitionPageUrl = $competition?->is_public
                    ? route('five-quillas-competition.page', ['slug' => $competition->public_slug])
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
                    'id' => $tournament->id,
                    'mes' => $monthNames[$start->month].' '.$start->year,
                    'fecha' => $dayLabel,
                    'fecha_inicio' => $start?->toDateString(),
                    'fecha_fin' => $end?->toDateString(),
                    'estado_torneo' => $end?->lt($today)
                        ? 'Finalizado'
                        : ($start?->gt($today) ? 'Próximo' : 'En curso'),
                    'torneo' => $tournament->name,
                    'disciplina_id' => $tournament->discipline_id,
                    'disciplina' => $tournament->discipline?->name ?? '',
                    'tipo_id' => $tournament->tournament_type_id,
                    'categorias' => $categories,
                    'club' => $tournament->venueName(),
                    'tipo' => $tournament->type?->name ?? '',
                    'oficial' => (bool) $tournament->type?->is_official,
                    'provincia' => $tournament->venue_type === 'external'
                        ? $tournament->externalVenueState?->federation?->short_name ?? $tournament->externalVenueState?->name ?? ''
                        : $club?->city?->state?->federation?->short_name ?? '',
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
                    'contactos' => $tournament->contacts
                        ->map(fn ($contact): string => "{$contact->name}: {$contact->phone}")
                        ->implode(' | '),
                    'desarrollo_disponible' => (bool) $competition?->is_public,
                    'desarrollo_slug' => $competition?->public_slug ?? '',
                    'desarrollo_estado' => $competition?->status ?? '',
                    'desarrollo_texto' => 'Ver resultados',
                    'desarrollo_mensaje' => 'Información no disponible en este momento',
                    'desarrollo_json_url' => $competitionJsonUrl,
                    'desarrollo_url' => $competitionPageUrl,
                ];
            });

        $years = Tournament::query()->whereNotNull('end_date')
            ->selectRaw('YEAR(end_date) as year')->distinct()->orderByDesc('year')->pluck('year')
            ->map(fn ($year): int => (int) $year)->values();

        return response()
            ->json([
                'data' => $rows,
                'meta' => [
                    'estado' => $view,
                    'total' => $rows->count(),
                    'fecha_referencia' => $today->toDateString(),
                    'anios' => $years,
                    'filtros' => [
                        'estado' => ['vigentes', 'finalizados', 'todos'],
                        'desde' => 'YYYY-MM-DD',
                        'hasta' => 'YYYY-MM-DD',
                        'anio' => 'YYYY',
                        'disciplina' => 'ID',
                        'tipo' => 'ID',
                        'categoria' => 'ID',
                        'buscar' => 'Texto',
                    ],
                ],
            ])
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0, s-maxage=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'Access-Control-Allow-Origin' => '*',
            ]);
    }
}
