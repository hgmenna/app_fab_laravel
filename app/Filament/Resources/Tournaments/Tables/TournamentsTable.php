<?php

namespace App\Filament\Resources\Tournaments\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Category;
use App\Models\Tournament;
use App\Services\AdminNotifier;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

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
                    ->width('14rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-tournament'])
                    ->extraCellAttributes(['class' => 'fab-col-tournament']),

                ImageColumn::make('flyer_path')
                    ->label('Flyer')
                    ->disk('public_path')
                    ->imageWidth(50)
                    ->imageHeight(38)
                    ->alignCenter()
                    ->extraImgAttributes(['class' => 'object-cover rounded-md'])
                    ->url(
                        fn (Tournament $record): ?string => filled($record->flyer_path)
                            ? Storage::disk('public_path')->url($record->flyer_path)
                            : null,
                        shouldOpenInNewTab: true,
                    )
                    ->width('6%'),

                ViewColumn::make('sport_details')
                    ->label('Datos')
                    ->view('filament.tables.columns.tournament-sport-details')
                    ->sortable(['tournament_type_id'])
                    ->width('9rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-sport-details'])
                    ->extraCellAttributes(['class' => 'fab-col-sport-details']),

                ViewColumn::make('conditions')
                    ->label('Of. / Hand.')
                    ->view('filament.tables.columns.tournament-conditions')
                    ->alignCenter()
                    ->width('6.5rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-conditions'])
                    ->extraCellAttributes(['class' => 'fab-col-conditions']),

                TextColumn::make('venue.name')
                    ->label('Sede')
                    ->sortable()
                    ->wrap()
                    ->width('12rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-venue'])
                    ->extraCellAttributes(['class' => 'fab-col-venue']),

                ImageColumn::make('publication_logo')
                    ->label('Fed.')
                    ->state(fn (Tournament $record): ?string => $record->publicationLogoPath())
                    ->disk('public_path')
                    ->imageSize(38)
                    ->square()
                    ->alignCenter()
                    ->url(
                        fn (Tournament $record): ?string => filled($path = $record->publicationLogoPath())
                            ? Storage::disk('public_path')->url($path)
                            : null,
                        shouldOpenInNewTab: true,
                    )
                    ->width('5%'),

                TextColumn::make('categories')
                    ->label('Cat.')
                    ->state(function (Tournament $record): string {
                        static $categories;

                        $categories ??= Category::query()
                            ->get(['id', 'code', 'name'])
                            ->keyBy('id');

                        return collect($record->categories ?? [])
                            ->map(function ($id) use ($categories): ?string {
                                $category = $categories->get($id);

                                return $category?->code ?: $category?->name;
                            })
                            ->filter()
                            ->implode(', ');
                    })
                    ->wrap()
                    ->width('14%'),

                ViewColumn::make('dates')
                    ->label('Fechas')
                    ->view('filament.tables.columns.tournament-dates')
                    ->sortable(['start_date'])
                    ->alignCenter()
                    ->width('9%'),

                TextColumn::make('registrations_count')
                    ->label('Insc.')
                    ->counts('registrations')
                    ->alignCenter()
                    ->tooltip('Inscriptos')
                    ->width('4.5rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-registrations'])
                    ->extraCellAttributes(['class' => 'fab-col-registrations']),

                ViewColumn::make('registration_options')
                    ->label('Pago / Insc.')
                    ->view('filament.tables.columns.tournament-registration-options')
                    ->alignCenter()
                    ->width('7rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-registration-options'])
                    ->extraCellAttributes(['class' => 'fab-col-registration-options']),

                IconColumn::make('regulatory_override')
                    ->label('Excep.')
                    ->boolean()
                    ->trueColor('warning')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true)
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
                    ->width('8.5rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-status'])
                    ->extraCellAttributes(['class' => 'fab-col-status']),

            ])
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'registrations:id,tournament_id,partner_player_id',
                'latestSuccessfulRegulationAudit',
                'type.publicationFederation',
                'venue.city.state.federation',
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
            ->recordActionsColumnLabel('Acc.')
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
