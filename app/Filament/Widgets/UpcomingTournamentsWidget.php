<?php

namespace App\Filament\Widgets;

use App\Models\Tournament;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingTournamentsWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'Agenda de torneos';

    protected static ?int $sort = 5;

    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $isGuestPanel = Filament::getCurrentPanel()?->getId() === 'guest';

        return $table
            ->query(Tournament::query()
                ->with(['discipline', 'type', 'venue', 'externalVenueState'])
                ->withCount('registrations')
                ->whereDate('end_date', '>=', today())
                ->when($isGuestPanel, fn ($query) => $query->whereIn('status', [
                    'published',
                    'in_progress',
                    'finished',
                ]))
                ->orderBy('start_date')
                ->orderBy('id'))
            ->columns([
                TextColumn::make('date_range')
                    ->label('Fechas')
                    ->state(fn (Tournament $record): string => $record->start_date?->isSameDay($record->end_date)
                        ? $record->start_date->format('d/m/Y')
                        : $record->start_date?->format('d/m').' – '.$record->end_date?->format('d/m/Y'))
                    ->icon('heroicon-m-calendar-days')
                    ->weight('bold'),
                TextColumn::make('name')
                    ->label('Torneo')
                    ->searchable()
                    ->wrap()
                    ->weight('bold'),
                TextColumn::make('discipline.name')
                    ->label('Disciplina')
                    ->badge()
                    ->color('info'),
                TextColumn::make('venue_name')
                    ->label('Sede')
                    ->state(fn (Tournament $record): string => $record->venueName())
                    ->wrap(),
                TextColumn::make('registrations_count')
                    ->label('Insc.')
                    ->numeric()
                    ->alignment('center'),
                TextColumn::make('registration_state')
                    ->label('Inscripciones')
                    ->state(fn (Tournament $record): string => $record->isRegistrationOpen() ? 'Abiertas' : 'Cerradas')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Abiertas' ? 'success' : 'gray'),
                TextColumn::make('status')
                    ->label('Estado del torneo')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'published' => 'Publicado',
                        'in_progress' => 'En curso',
                        'finished' => 'Finalizado',
                        'cancelled' => 'Cancelado',
                        'aprobado' => 'Aprobado',
                        'pendiente_verificacion' => 'Pendiente',
                        'excepcion_autorizada' => 'Excepción autorizada',
                        default => ucfirst(str_replace('_', ' ', (string) $state)),
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'published', 'aprobado' => 'success',
                        'in_progress', 'excepcion_autorizada' => 'warning',
                        'cancelled' => 'danger',
                        'finished' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (?string $state): string => match ($state) {
                        'published', 'aprobado' => 'heroicon-m-check-circle',
                        'in_progress' => 'heroicon-m-play-circle',
                        'finished' => 'heroicon-m-flag',
                        'cancelled' => 'heroicon-m-x-circle',
                        default => 'heroicon-m-pencil-square',
                    }),
            ])
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->striped();
    }
}
