<?php

namespace App\Filament\Resources\Tournaments\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use App\Filament\Resources\Tournaments\TournamentResource;
use App\Services\AdminNotifier;
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
                    ->wrap()
                    ->width('16%'),

                TextColumn::make('type.code')
                    ->label('Tipo')
                    ->sortable()
                    ->alignCenter()
                    ->wrap()
                    ->visibleFrom('md')
                    ->width('6%'),

                TextColumn::make('type.participation_mode')
                    ->label('Mod.')
                    ->formatStateUsing(fn (?string $state): string => $state === 'pairs' ? 'Par' : 'Ind.')
                    ->alignCenter()
                    ->visibleFrom('lg')
                    ->width('6%'),

                TextColumn::make('venue.name')
                    ->label('Club')
                    ->sortable()
                    ->wrap()
                    ->visibleFrom('md')
                    ->width('14%'),

                TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date('d/m/y')
                    ->sortable()
                    ->alignCenter()
                    ->width('7%'),

                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/y')
                    ->sortable()
                    ->alignCenter()
                    ->visibleFrom('md')
                    ->width('7%'),

                TextColumn::make('registrations_count')
                    ->label('Insc.')
                    ->counts('registrations')
                    ->alignCenter()
                    ->visibleFrom('md')
                    ->width('5%'),

                TextColumn::make('participants_count')
                    ->label('Part.')
                    ->getStateUsing(fn ($record): int => $record->registrations
                        ->sum(fn ($registration): int => $registration->partner_player_id ? 2 : 1))
                    ->alignCenter()
                    ->visibleFrom('lg')
                    ->width('5%'),

                IconColumn::make('is_payment_enabled')
                    ->label('Pago')
                    ->boolean()
                    ->alignCenter()
                    ->visibleFrom('lg')
                    ->width('4%'),

                IconColumn::make('registration_enabled')
                    ->label('Insc. abierta')
                    ->boolean()
                    ->alignCenter()
                    ->visibleFrom('lg')
                    ->width('6%'),

                IconColumn::make('regulatory_override')
                    ->label('Excep.')
                    ->boolean()
                    ->trueColor('warning')
                    ->alignCenter()
                    ->visibleFrom('lg')
                    ->width('5%'),

                TextColumn::make('latestSuccessfulRegulationAudit.result')
                    ->label('Estado')
                    ->state(fn ($record): string => $record->latestSuccessfulRegulationAudit?->result ?? 'pending')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Aprobado',
                        'overridden' => 'Excepción autorizada',
                        default => 'Pendiente de verificación',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'overridden' => 'warning',
                        default => 'gray',
                    })
                    ->alignCenter()
                    ->wrap()
                    ->width('10%'),
            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'registrations:id,tournament_id,partner_player_id',
                'latestSuccessfulRegulationAudit',
            ]))
            ->extraAttributes(['class' => 'fab-tournaments-table'])
            ->defaultSort('start_date', direction: 'asc')
            ->recordActions([
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    AdminNotifier::notifyAction(GlobalDeleteAction::make(), null, 'eliminó', ['name'], 'Torneos'),
                    TournamentResource::inscriptionsAction(),
                    TournamentResource::resumenAction(),
                ])
                    ->iconButton()
                    ->tooltip('Acciones'),
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
                                fn (Builder $query, string $date): Builder => $query->whereDate('end_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, string $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    }),
            ]);
    }
}
