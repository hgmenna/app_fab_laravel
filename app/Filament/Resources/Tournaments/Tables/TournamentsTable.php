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
use Filament\Tables\Enums\RecordActionsPosition;
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
                    ->width('12rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-tournament'])
                    ->extraCellAttributes(['class' => 'fab-col-tournament']),

                ViewColumn::make('dates')
                    ->label('Inicio / Fin')
                    ->view('filament.tables.columns.tournament-dates')
                    ->sortable(['start_date'])
                    ->alignCenter()
                    ->width('7rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-dates'])
                    ->extraCellAttributes(['class' => 'fab-col-dates']),

                ViewColumn::make('registration_dates')
                    ->label('Apertura / Cierre')
                    ->view('filament.tables.columns.tournament-registration-dates')
                    ->alignCenter()
                    ->width('7rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-dates'])
                    ->extraCellAttributes(['class' => 'fab-col-dates']),

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
                    ->width('4.25rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-flyer'])
                    ->extraCellAttributes(['class' => 'fab-col-flyer']),

                ViewColumn::make('sport_details')
                    ->label('Datos')
                    ->view('filament.tables.columns.tournament-sport-details')
                    ->sortable(['tournament_type_id'])
                    ->width('8rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-sport-details'])
                    ->extraCellAttributes(['class' => 'fab-col-sport-details']),

                ViewColumn::make('conditions')
                    ->label('Of. / Hand.')
                    ->view('filament.tables.columns.tournament-conditions')
                    ->alignCenter()
                    ->width('6rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-conditions'])
                    ->extraCellAttributes(['class' => 'fab-col-conditions']),

                TextColumn::make('venue.name')
                    ->label('Sede')
                    ->placeholder('A definir')
                    ->sortable()
                    ->wrap()
                    ->width('10rem')
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
                    ->width('4.25rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-federation'])
                    ->extraCellAttributes(['class' => 'fab-col-federation']),

                ViewColumn::make('categories')
                    ->label('Cat.')
                    ->state(function (Tournament $record): array {
                        static $categories;

                        $categories ??= Category::query()
                            ->get(['id', 'code', 'name', 'order'])
                            ->keyBy('id');

                        return collect($record->categories ?? [])
                            ->map(fn ($id) => $categories->get($id))
                            ->filter()
                            ->sortBy(fn (Category $category) => [
                                $category->order ?? PHP_INT_MAX,
                                $category->name,
                            ])
                            ->map(fn (Category $category): string => $category->code ?: $category->name)
                            ->values()
                            ->all();
                    })
                    ->view('filament.tables.columns.tournament-categories')
                    ->width('4.5rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-categories'])
                    ->extraCellAttributes(['class' => 'fab-col-categories']),

                TextColumn::make('registrations_count')
                    ->label('# Insc')
                    ->counts('registrations')
                    ->alignCenter()
                    ->tooltip('Inscriptos')
                    ->width('4.25rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-registrations'])
                    ->extraCellAttributes(['class' => 'fab-col-registrations']),

                ViewColumn::make('registration_options')
                    ->label('Pago / Insc.')
                    ->view('filament.tables.columns.tournament-registration-options')
                    ->alignCenter()
                    ->width('6rem')
                    ->extraHeaderAttributes(['class' => 'fab-col-registration-options'])
                    ->extraCellAttributes(['class' => 'fab-col-registration-options']),

                IconColumn::make('regulatory_override')
                    ->label('Excep')
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
                    ->width('7.5rem')
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
                    ->dropdownPlacement('bottom-start')
                    ->tooltip('Acciones'),
            ], position: RecordActionsPosition::BeforeColumns)
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
