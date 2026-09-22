<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Category;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;

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

        $categoryNames = Category::query()->pluck('name', 'id');

        $rows = Tournament::query()
            ->with([
                'discipline:id,name',
                'type:id,name',
                'venue:id,name,address,lat,lng',
            ])
            ->withCount('registrations')
            ->whereDate('start_date', '>', today('America/Argentina/Buenos_Aires'))
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->map(function (Tournament $tournament) use ($monthNames, $categoryNames): array {
                $club = $tournament->venue;

                $categories = collect($tournament->categories ?? [])
                    ->map(fn ($id) => $categoryNames->get($id))
                    ->filter()
                    ->implode(', ');

                $mapUrl = null;

                if ($club?->lat !== null && $club?->lng !== null) {
                    $mapUrl = 'https://www.google.com/maps/search/?api=1&query='
                        . $club->lat . ',' . $club->lng;
                } elseif ($club?->address) {
                    $mapUrl = 'https://www.google.com/maps/search/?api=1&query='
                        . rawurlencode($club->address . ', ' . $club->name);
                }

                $registrationUrl = TournamentResource::getUrl(
                    'registrations',
                    ['record' => $tournament->id],
                    panel: 'guest',
                );

                $start = $tournament->start_date;
                $end = $tournament->end_date;
                $dayLabel = $start->format('j');

                if ($end && ! $end->isSameDay($start)) {
                    if ($start->format('Y-m') === $end->format('Y-m')) {
                        $dayLabel .= '–' . $end->format('j');
                    } else {
                        $startYear = $start->year !== $end->year ? ' ' . $start->year : '';
                        $endYear = $start->year !== $end->year ? ' ' . $end->year : '';

                        $dayLabel = $start->format('j') . ' '
                            . substr($monthNames[$start->month], 0, 3) . $startYear
                            . ' – ' . $end->format('j') . ' '
                            . substr($monthNames[$end->month], 0, 3) . $endYear;
                    }
                }

                return [
                    'mes' => $monthNames[$start->month] . ' ' . $start->year,
                    'fecha' => $dayLabel,
                    'torneo' => $tournament->name,
                    'disciplina' => $tournament->discipline?->name ?? '',
                    'categorias' => $categories,
                    'club' => $club?->name ?? '',
                    'tipo' => $tournament->type?->name ?? '',
                    'inscriptos' => $tournament->registrations_count,
                    'inscripcion' => $registrationUrl . '||Inscribirse',
                    'ubicacion' => $mapUrl ? $mapUrl . '||Ver ubicación' : '',
                ];
            });

        return response()
            ->json(['data' => $rows])
            ->header('Cache-Control', 'no-store, max-age=0');
    }
}
