<?php

namespace App\Filament\Resources\Tournaments\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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

                TextColumn::make('type.participation_mode')
                    ->label('Modalidad')
                    ->formatStateUsing(fn (?string $state): string => $state === 'pairs' ? 'Parejas' : 'Individual')
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
                    ->label('Inscripciones')
                    ->counts('registrations')
                    ->alignCenter(),

                TextColumn::make('participants_count')
                    ->label('Participantes')
                    ->getStateUsing(fn ($record): int => $record->registrations
                        ->sum(fn ($registration): int => $registration->partner_player_id ? 2 : 1))
                    ->alignCenter(),

                IconColumn::make('is_payment_enabled')
                    ->label('Pago')
                    ->alignCenter(),

                IconColumn::make('registration_enabled')
                    ->label('Inscripción habilitada')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'registrations:id,tournament_id,partner_player_id',
            ]))
            ->defaultSort('start_date', direction: 'asc')
            ->recordActions([
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    GlobalDeleteAction::make(),
                    TournamentResource::inscriptionsAction(),
                    TournamentResource::resumenAction(),
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
                Filter::make('start_date')
                    ->label('Fecha de inicio')
                    ->schema([
                        DatePicker::make('from')
                            ->label('Desde fecha')
                            ->default(today('America/Argentina/Buenos_Aires')->toDateString())
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('until')
                            ->label('Hasta fecha')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),
            ]);
    }
}
