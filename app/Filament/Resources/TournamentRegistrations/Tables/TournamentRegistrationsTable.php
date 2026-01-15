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

class TournamentRegistrationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
             ->columns([
                TextColumn::make('tournament.name')
                    ->label('Torneo')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('player.full_name')
                    ->label('Jugador')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('slot.starts_at')
                    ->label('Horario')
                    ->dateTime('d/m H:i')
                    ->sortable(),

                TextColumn::make('slot.max_players')
                    ->label('Cupos totales'),

                TextColumn::make('slot.registrations_count')
                    ->label('Inscriptos')
                    ->counts('registrations'),

                TextColumn::make('created_at')
                    ->label('Fecha inscripción')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');

    }
}
