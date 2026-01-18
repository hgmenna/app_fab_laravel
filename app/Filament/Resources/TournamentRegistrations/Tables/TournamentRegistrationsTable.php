<?php

namespace App\Filament\Resources\TournamentRegistrations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use App\Models\Tournament;
use Carbon\Carbon;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;

class TournamentRegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                Tournament::query()
                    ->where('registration_open_at', '<=', now())
                    ->where('registration_close_at', '>=', now())
                    ->where('start_date', '>', now())
            )

            ->columns([
                TextColumn::make('name')
                    ->label('Torneo')
                    ->alignCenter()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tournament.type.name')
                    ->label('Tipo de torneo')
                    ->searchable(),

                TextColumn::make('start_date')
                    ->label('Fecha inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('registration_close_at')
                    ->label('Cierre inscripción')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('registrations_count')
                    ->label('Inscriptos')
                    ->state(fn($record) => $record->registrations()->count()),

            ])

            ->recordActions([
                Action::make('inscribir')
                    ->label('Inscribirse')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->url(fn ($record) => route(
                        'filament.admin.resources.tournament-registrations.create',
                        ['tournament_id' => $record->id]
                    )),
                    Action::make('verJugadores')
                        ->label('Ver jugadores inscriptos')
                        ->icon('heroicon-o-users')
                        ->color('info')
                        // Redirige a la página 'players' pasando el registro del torneo [3]
                        ->url(fn ($record) => TournamentRegistrationResource::getUrl('players', ['record' => $record])),
            ])

            ->defaultSort('start_date', 'asc');

    }

}
