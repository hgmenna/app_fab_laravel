<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Discipline;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class UpcomingByDisciplineWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected string $view = 'filament.widgets.upcoming-by-discipline-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $disciplines = Discipline::query()
            ->withCount(['tournaments as upcoming_count' => fn ($query) => $query
                ->whereDate('end_date', '>=', today())])
            ->orderBy('name')
            ->get()
            ->filter(fn (Discipline $discipline): bool => $discipline->upcoming_count > 0)
            ->values();
        $panelId = Filament::getCurrentPanel()?->getId();

        return [
            'disciplines' => $disciplines,
            'total' => $disciplines->sum('upcoming_count'),
            'tournamentsUrl' => TournamentResource::getUrl('index', panel: $panelId),
        ];
    }
}
