<?php

namespace App\Filament\Widgets;

use App\Models\Club;
use App\Models\Federation;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SportsSummaryWidget extends StatsOverviewWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Resumen deportivo';

    protected ?string $description = 'Información actualizada del sistema federativo';

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'xl' => 3,
    ];

    protected function getStats(): array
    {
        $now = now();

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

            Stat::make('Federaciones', Federation::query()->count())
                ->description('Entidades federativas registradas')
                ->descriptionIcon('heroicon-m-flag')
                ->color('warning'),

            Stat::make('Torneos próximos y en curso', Tournament::query()
                ->where('is_active', true)
                ->whereDate('end_date', '>=', today())
                ->count())
                ->description('Según la fecha de finalización')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Inscripciones abiertas', Tournament::query()
                ->where('is_active', true)
                ->where('registration_enabled', true)
                ->where(fn ($query) => $query
                    ->whereNull('registration_open_at')
                    ->orWhere('registration_open_at', '<=', $now))
                ->where(fn ($query) => $query
                    ->whereNull('registration_close_at')
                    ->orWhere('registration_close_at', '>=', $now))
                ->count())
                ->description(TournamentRegistration::query()
                    ->where('status', 'aprobado')
                    ->whereHas('tournament', fn ($query) => $query->whereYear('end_date', now()->year))
                    ->count().' inscripciones aprobadas en '.now()->year)
                ->descriptionIcon('heroicon-m-pencil-square')
                ->color('success'),
        ];
    }
}
