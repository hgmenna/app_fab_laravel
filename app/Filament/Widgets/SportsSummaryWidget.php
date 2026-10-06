<?php

namespace App\Filament\Widgets;

use App\Models\Club;
use App\Models\Player;
use App\Models\TournamentRegistration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SportsSummaryWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        return [
            Stat::make('Afiliados activos', Player::query()->where('is_active', true)->count())
                ->description('Jugadores activos registrados')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Habilitados para competir', Player::query()
                ->where('is_active', true)
                ->where('is_enabled_to_compete', true)
                ->count())
                ->description('Con habilitación deportiva vigente')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Clubes activos', Club::query()
                ->withoutGlobalScope('ordered')
                ->where('is_active', true)
                ->count())
                ->description('Instituciones registradas')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('info'),

            Stat::make('Inscripciones', TournamentRegistration::query()
                ->where('status', 'aprobado')
                ->whereHas('tournament', fn ($query) => $query->whereYear('end_date', now()->year))
                ->count())
                ->description('Registradas en '.now()->year)
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color('success'),
        ];
    }
}
