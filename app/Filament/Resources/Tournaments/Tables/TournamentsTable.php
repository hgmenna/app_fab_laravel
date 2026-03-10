<?php

namespace App\Filament\Resources\Tournaments\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TournamentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Torneo')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('type.code')
                    ->label('Tipo')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('venue.name')
                    ->label('Club organizador')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date()
                    ->sortable()
                    ->alignCenter(),    
    
                TextColumn::make('registrations_count')
                    ->label('Inscriptos')
                    ->counts('registrations')
                    ->alignCenter(),

                IconColumn::make('is_payment_enabled')
                    ->label('Pago')
                    ->alignCenter(),
            ])->defaultSort('start_date', direction:'desc')
            ->recordActions([
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    GlobalDeleteAction::make(),
                    TournamentResource::inscriptionsAction(),
                ]),
            ])
            ->filters([
                SelectFilter::make('discipline_id')
                    ->relationship('discipline', 'name')
                    ->label('Disciplina'),
                SelectFilter::make('type_id')
                    ->relationship('type', 'name')
                    ->label('Tipo de Torneo')
                    ->searchable(),
            ]);
    }

}
