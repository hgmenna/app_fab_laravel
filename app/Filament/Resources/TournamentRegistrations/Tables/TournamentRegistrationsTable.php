<?php

namespace App\Filament\Resources\TournamentRegistrations\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use App\Mail\TournamentRegistrationNotification;
use App\Models\Category;
use App\Models\GeneralRanking;
use App\Models\ThreeCushionStageResult;
use App\Models\TournamentRegistration;
use App\Models\TournamentSlot;
use App\Services\AdminNotifier;
use App\Services\ThreeCushionRankingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class TournamentRegistrationsTable
{
    protected static string $resource = TournamentRegistrationResource::class;

    public static function configure(Table $table, $tournament): Table
    {
        return $table
            ->columns([
                TextColumn::make('tournamentModality.modality.name')
                    ->label('Modalidad')->badge()->sortable(),

                TextColumn::make('participant_names')
                    ->label('Integrantes')
                    ->getStateUsing(fn (TournamentRegistration $record): string => $record->participant_names ?: 'Sin integrantes')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('participants.player', fn (Builder $players): Builder => $players
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"))
                        ->orWhereHas('player', fn (Builder $player): Builder => $player
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%"))
                        ->orWhereHas('partner', fn (Builder $partner): Builder => $partner
                            ->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")))
                    ->wrap(),

                TextColumn::make('participants.player.club.name')
                    ->label('Clubes')->listWithLineBreaks()->bulleted(),

                TextColumn::make('participants.player.category.name')
                    ->label('Categorías')->listWithLineBreaks()->bulleted(),

                // Columna para la Categoría del Ranking
                TextColumn::make('ranking_category')
                    ->label('C/R')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return GeneralRanking::where('first_name', $record->player?->first_name)
                            ->where('last_name', $record->player?->last_name)
                            ->value('category') ?? '-';
                    }
                    ),

                // Columna para el Ranking General (RG)
                TextColumn::make('ranking_rg')
                    ->label('RG')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return GeneralRanking::where('first_name', $record->player?->first_name)
                            ->where('last_name', $record->player?->last_name)
                            ->value('RG') ?? '-';
                    }
                    ),

                TextColumn::make('tournamentInstance.description')
                    ->label('Posicion')
                    ->visible(fn () => Auth::user()?->canGloballyOrInAnyDiscipline('EditField')
                    ),

                TextColumn::make('points')
                    ->label('Puntos')
                    ->visible(fn () => Auth::user()?->canGloballyOrInAnyDiscipline('EditField'))
                    ->formatStateUsing(fn ($record) => $record->points !== null
                            ? number_format($record->points, 2)
                            : '—'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'aprobado' => 'success',
                        'denegado' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->alignCenter(),

                ImageColumn::make('payment_file')
                    ->label('pagos')
                    ->disk('public_path')
                    ->square(60)
                    ->alignCenter(),

            ])
            ->filters([
                SelectFilter::make('category')
                    ->label('Categoria')
                    ->options(
                        Category::orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->query(function ($query, $value) {
                        if (! $value) {
                            return; // 🔥 evita 500 cuando no hay filtro
                        }

                        $query->whereHas('participants.player.category', function ($q) use ($value) {
                            $q->where('id', $value);
                        });
                    })

                    ->searchable(),
                SelectFilter::make('tournament_slot_id')
                    ->label('Horario')
                    ->multiple()
                    ->options(function ($livewire) use ($tournament) {
                        // Buscamos el torneo: ya sea el pasado por parámetro o el de la página actual
                        $t = $tournament ?? (method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord() : null);

                        // Si hay torneo, devolvemos sus slots; si no, un array vacío
                        return $t
                            ? $t->slots
                                ->filter(fn ($slot) => $slot->starts_at !== null && $slot->max_players !== null)
                                ->pluck('name', 'id')
                            : [];
                    }),
                SelectFilter::make('tournament_modality_id')
                    ->label('Modalidad')
                    ->options(fn () => $tournament?->tournamentModalities()->with('modality')->get()
                        ->mapWithKeys(fn ($item) => [$item->id => $item->modality->name])->all() ?? []),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(function () {
                        return TournamentRegistration::query()
                            ->distinct()
                            ->whereNotNull('status')
                            ->pluck('status', 'status')
                            ->map(fn ($state) => ucfirst($state)) // Capitaliza la primera letra para la vista
                            ->toArray();
                    }),
                Filter::make('posicion_sin_asignar')
                    ->label('Sin posición asignada')
                    ->toggle()
                    ->query(function (Builder $query) {
                        return $query
                            ->where(function ($q) {
                                $q->whereDoesntHave('tournamentInstance') // sin relación
                                    ->orWhereHas('tournamentInstance', function ($t) {
                                        $t->whereNull('description')
                                            ->orWhere('description', ''); // string vacío
                                    });
                            });
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Inscribirse al torneo')
                    ->icon('heroicon-o-plus')
                    ->modal()
                    ->modalHeading('Nueva Inscripción')
                    ->disabled(function ($livewire) use ($tournament) {

                        $user = Auth::user();
                        if ($user?->name === 'super-admin') {
                            return false;
                        }

                        // 1. Resolvemos el torneo de forma dinámica [1, 2]
                        $t = $tournament ?? (method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord() : null);

                        // Si no hay torneo (vista general), no se permite crear sin elegir uno primero en el form
                        if (! $t) {
                            return false;
                        }

                        // 2. Validamos si las inscripciones están abiertas [104 de tu error]
                        return ! $t->isRegistrationOpen();
                    })
                    ->mutateDataUsing(function (array $data, $livewire): array {
                        // 3. Si es "nested", aseguramos que el ID del torneo se asocie correctamente [2, 3]
                        if (method_exists($livewire, 'getOwnerRecord')) {
                            $data['tournament_id'] = $livewire->getOwnerRecord()->id;
                        }

                        return $data;
                    })
                    ->successNotificationTitle('Inscripción realizada con éxito')
                    // Opcional: Lógica después de crear [105 de tu error]
                    ->after(function ($livewire) {
                        $livewire->dispatch('refreshTable');
                    }
                    ),
                self::bulkSlotAssignmentAction($tournament),
                self::bulkScoringAction($tournament),
                Action::make('exportarInscripcionesPdf')
                    ->label('Exportar PDF')
                    ->action(function ($livewire) {
                        $tournament = $livewire->getOwnerRecord();

                        if ($tournament instanceof Collection) {
                            $tournament = $tournament->first();
                        }

                        // Obtener la pestaña activa
                        $activeTab = $livewire->activeTab; // Por ejemplo: "all" o "slot_5" [2, 3]
                        $slotId = null;

                        // Si la pestaña no es "all", extraer el ID del slot
                        if ($activeTab !== 'all' && str_starts_with($activeTab, 'slot_')) {
                            $slotId = str_replace('slot_', '', $activeTab);
                        }

                        $statusFilter = $livewire->tableFilters['status']['value'] ?? null;

                        // Pasar el slotId al método de exportación
                        return TournamentRegistrationResource::exportRegistrationsToPdf($tournament, $slotId, $statusFilter);
                    }
                    ),
            ])
            ->recordActions([
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make()
                        ->visible(fn () => Auth::user()?->canGloballyOrInAnyDiscipline('EditField'))
                        ->after(function (Model $record) {
                            $tournamentName = $record->tournament?->name ?? 'el torneo';

                            Mail::to(AdminNotifier::recipientEmails())
                                ->send(new TournamentRegistrationNotification($record, 'Actualizacion de inscripcion'));

                            AdminNotifier::send(
                                null,
                                $record,
                                'modificó la inscripción de',
                                'participant_names',
                                "el torneo {$tournamentName}",
                                false,
                            );
                        }
                        ),
                    GlobalDeleteAction::make()
                        ->visible(fn () => Auth::user()?->canGloballyOrInAnyDiscipline('EditField'))
                        ->after(function (Model $record) {
                            $tournamentName = $record->tournament?->name ?? 'el torneo';

                            try {
                                AdminNotifier::send(
                                    null,
                                    $record,
                                    'eliminó la inscripción de',
                                    'participant_names',
                                    "el torneo {$tournamentName}",
                                    false,
                                );
                            } catch (\Throwable $exception) {
                                report($exception);
                            }
                        })
                        ->before(function (Model $record) {
                            $record->loadMissing([
                                'tournament',
                                'slot',
                                'participants.player.club',
                                'participants.player.category',
                            ]);

                            try {
                                Mail::to(AdminNotifier::recipientEmails())->send(
                                    new TournamentRegistrationNotification($record, 'Inscripción eliminada')
                                );
                            } catch (\Throwable $exception) {
                                report($exception);
                            }
                        }),
                    TournamentRegistrationResource::AsignInstanceAction(),
                    Action::make('resultadoTresBandas')
                        ->label('Datos 3 Bandas')
                        ->icon('heroicon-o-chart-bar-square')
                        ->modalHeading('Resultado de la etapa - Carambola 3 Bandas')
                        ->modalSubmitActionLabel('Guardar datos de la etapa')
                        ->fillForm(function (TournamentRegistration $record): array {
                            $result = ThreeCushionStageResult::query()
                                ->where('tournament_registration_id', $record->id)
                                ->first();

                            return [
                                'caroms' => $result?->caroms,
                                'innings' => $result?->innings,
                                'best_match_average' => $result?->best_match_average,
                                'high_run' => $result?->high_run,
                            ];
                        })
                        ->schema([
                            TextInput::make('caroms')->label('Carambolas totales')->integer()->minValue(0)->required(),
                            TextInput::make('innings')->label('Entradas totales')->integer()->minValue(1)->required(),
                            TextInput::make('best_match_average')->label('Mejor promedio particular')->numeric()->minValue(0)->step(0.000001)->required(),
                            TextInput::make('high_run')->label('Serie mayor')->integer()->minValue(0)->required(),
                        ])
                        ->action(function (TournamentRegistration $record, array $data): void {
                            ThreeCushionStageResult::query()->updateOrCreate(
                                ['tournament_registration_id' => $record->id],
                                $data,
                            );
                        })
                        ->visible(fn (TournamentRegistration $record): bool => ThreeCushionRankingService::isThreeCushion($record->tournament?->discipline)
                            && $record->status === 'aprobado'
                            && $record->points !== null
                            && (Auth::user()?->canGloballyOrForDiscipline(
                                'AssignTournamentScore',
                                $record->tournament?->discipline_id,
                            ) ?? false)),
                    Action::make('cambiarEstado')
                        ->label('Cambiar Estado')
                        ->icon(Heroicon::CurrencyDollar)
                        ->schema([
                            Select::make('status')
                                ->label('Nuevo Estado')
                                ->options([
                                    'pendiente' => 'Pendiente',
                                    'aprobado' => 'Aprobado',
                                    'denegado' => 'Denegado',
                                ])
                                ->required(),
                        ])
                        ->action(function (Model $record, array $data): void {
                            $record->update(['status' => $data['status']]);
                            $record->load(['slot', 'participants.player.club', 'participants.player.category']);
                            Mail::to(AdminNotifier::recipientEmails())
                                ->send(new TournamentRegistrationNotification($record, 'Actualizacion de estado de inscripcion'));

                            $tournamentName = $record->tournament?->name ?? 'el torneo';

                            AdminNotifier::send(
                                null,
                                $record,
                                'Actualizó estado de la inscripción de',
                                'participant_names',
                                "el torneo {$tournamentName}",
                                false,
                            );
                        })
                        ->visible(fn () => (Auth::user()?->canGloballyOrInAnyDiscipline('UpdateStatusTournament') ?? false)
                        ),
                    Action::make('pdf')
                        ->label('PDF')
                        ->icon('heroicon-o-document')
                        ->color('primary')
                        ->action(function ($record) {
                            $url = \App\Services\TournamentRegistrationPdfService::generate($record);

                            return redirect()->to($url);
                        }),
                ]),
            ]
            );
    }

    private static function bulkSlotAssignmentAction($tournament): Action
    {
        return Action::make('asignarHorariosMasivos')
            ->label('Asignar horarios')
            ->icon('heroicon-o-clock')
            ->color('info')
            ->modalHeading('Asignación masiva de horarios')
            ->modalDescription('Elegí el horario de destino y marcá las inscripciones que todavía no tienen un horario asignado.')
            ->modalSubmitActionLabel('Asignar horario seleccionado')
            ->modalWidth('7xl')
            ->visible(function ($livewire) use ($tournament): bool {
                $resolvedTournament = self::resolveTournament($tournament, $livewire);

                return $resolvedTournament
                    && (Auth::user()?->canGloballyOrForDiscipline(
                        'Update:TournamentRegistration',
                        $resolvedTournament->discipline_id,
                    ) ?? false);
            })
            ->schema([
                Select::make('target_slot_id')
                    ->label('Horario a asignar')
                    ->placeholder('Seleccioná el horario de destino')
                    ->options(fn ($livewire): array => self::assignableSlotOptions(
                        self::resolveTournament($tournament, $livewire)
                    ))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn (Set $set) => $set('registration_ids', [])),

                Placeholder::make('registration_table_header')
                    ->hiddenLabel()
                    ->content(new HtmlString(<<<'HTML'
                        <style>
                            .fab-slot-assignment-header,.fab-slot-assignment-row{display:grid;grid-template-columns:minmax(12rem,2fr) minmax(7rem,1fr) minmax(5rem,.7fr) minmax(10rem,1.4fr) minmax(7rem,1fr);align-items:center;gap:.65rem;width:100%}
                            .fab-slot-assignment-header{padding:.55rem .75rem;border-radius:.5rem;background:rgba(100,116,139,.12);font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#64748b}
                            .fab-slot-assignment-row{font-size:.76rem;line-height:1.3}
                            .fab-slot-assignment-row strong{font-size:.8rem}
                            @media(max-width:700px){.fab-slot-assignment-header{display:none}.fab-slot-assignment-row{grid-template-columns:1fr;gap:.2rem}.fab-slot-assignment-row span:not(:first-child)::before{content:attr(data-label) ': ';font-weight:800;color:#64748b}}
                        </style>
                        <div class="fab-slot-assignment-header"><span>Integrantes</span><span>Modalidad</span><span>Categoría</span><span>Club</span><span>Horario actual</span></div>
                        HTML))
                    ->visible(fn (Get $get): bool => filled($get('target_slot_id')))
                    ->columnSpanFull(),

                CheckboxList::make('registration_ids')
                    ->label('Inscripciones pendientes de horario')
                    ->options(fn (Get $get, $livewire): array => self::unassignedRegistrationOptions(
                        self::resolveTournament($tournament, $livewire),
                        filled($get('target_slot_id')) ? (int) $get('target_slot_id') : null,
                    ))
                    ->required()
                    ->minItems(1)
                    ->searchable()
                    ->allowHtml()
                    ->bulkToggleable()
                    ->columns(1)
                    ->helperText(fn (Get $get): string => filled($get('target_slot_id'))
                        ? 'Marcá los jugadores o parejas que pasarán al horario seleccionado.'
                        : 'Primero seleccioná el horario de destino para ver las inscripciones disponibles.')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, $livewire) use ($tournament): void {
                $resolvedTournament = self::resolveTournament($tournament, $livewire);

                abort_unless($resolvedTournament, 404);
                abort_unless(
                    Auth::user()?->canGloballyOrForDiscipline(
                        'Update:TournamentRegistration',
                        $resolvedTournament->discipline_id,
                    ),
                    403,
                );

                $targetSlot = $resolvedTournament->slots()
                    ->where('is_active', true)
                    ->whereKey((int) ($data['target_slot_id'] ?? 0))
                    ->first();

                if (! $targetSlot || self::isUnassignedSlotName($targetSlot->name)) {
                    throw ValidationException::withMessages([
                        'target_slot_id' => 'Seleccioná un horario activo válido.',
                    ]);
                }

                $registrationIds = collect($data['registration_ids'] ?? [])
                    ->filter()
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values();

                $eligibleRegistrations = $resolvedTournament->registrations()
                    ->whereKey($registrationIds)
                    ->where('tournament_modality_id', $targetSlot->tournament_modality_id)
                    ->where(function (Builder $query): void {
                        $query->whereNull('tournament_slot_id')
                            ->orWhereHas('slot', fn (Builder $slotQuery): Builder => $slotQuery
                                ->whereRaw('LOWER(TRIM(name)) = ?', ['sin asignar']));
                    })
                    ->with(['participants.player', 'slot'])
                    ->get();

                if ($registrationIds->isEmpty() || $eligibleRegistrations->count() !== $registrationIds->count()) {
                    throw ValidationException::withMessages([
                        'registration_ids' => 'Solo se pueden seleccionar inscripciones sin horario o con el horario SIN ASIGNAR que correspondan a la modalidad elegida.',
                    ]);
                }

                if ($targetSlot->max_players !== null) {
                    $availablePlaces = max(0, $targetSlot->max_players - $targetSlot->occupiedPlaces());
                    $requiredPlaces = $eligibleRegistrations
                        ->whereNotIn('status', ['denegado', 'rechazado'])
                        ->count();

                    if ($requiredPlaces > $availablePlaces) {
                        throw ValidationException::withMessages([
                            'registration_ids' => "El horario seleccionado tiene {$availablePlaces} cupo(s) disponible(s) y se intentan asignar {$requiredPlaces} inscripción(es).",
                        ]);
                    }
                }

                DB::transaction(function () use ($eligibleRegistrations, $targetSlot): void {
                    foreach ($eligibleRegistrations as $registration) {
                        $registration->update(['tournament_slot_id' => $targetSlot->getKey()]);
                    }
                });

                $targetSlot->loadMissing('tournament');
                AdminNotifier::sendBulk(
                    $eligibleRegistrations,
                    "asignó el horario {$targetSlot->name} a",
                    'participant_names',
                    'inscripciones del torneo '.($targetSlot->tournament?->name ?? ''),
                );

                $livewire->dispatch('refreshTable');
            })
            ->successNotificationTitle('Horario asignado correctamente');
    }

    private static function bulkScoringAction($tournament): Action
    {
        return Action::make('asignarPuntajesMasivos')
            ->label('Asignar puntajes')
            ->icon('heroicon-o-list-bullet')
            ->color('primary')
            ->modalHeading('Asignación masiva de puntajes')
            ->modalDescription('Elegí una posición y marcá los jugadores o parejas que recibirán el mismo puntaje.')
            ->modalSubmitActionLabel('Guardar todos los puntajes')
            ->modalWidth('7xl')
            ->visible(function ($livewire) use ($tournament): bool {
                $resolvedTournament = self::resolveTournament($tournament, $livewire);

                return $resolvedTournament
                    && (bool) $resolvedTournament->type?->assigns_points
                    && $resolvedTournament->type?->scoring_method === 'position'
                    && (Auth::user()?->canGloballyOrForDiscipline(
                        'AssignTournamentScore',
                        $resolvedTournament->discipline_id,
                    ) ?? false)
                    && $resolvedTournament->start_date < now();
            })
            ->schema([
                Select::make('tournament_instance_id')
                    ->label('Posición oficial')
                    ->options(function ($livewire) use ($tournament): array {
                        $resolvedTournament = self::resolveTournament($tournament, $livewire);

                        if (! self::usesOfficialInstances($resolvedTournament)) {
                            return [];
                        }

                        return self::scoringRuleOptions($resolvedTournament, true);
                    })
                    ->searchable()
                    ->preload()
                    ->required(fn ($livewire): bool => self::usesOfficialInstances(self::resolveTournament($tournament, $livewire)))
                    ->visible(fn ($livewire): bool => self::usesOfficialInstances(self::resolveTournament($tournament, $livewire))),

                Select::make('result_code')
                    ->label('Resultado')
                    ->options(function ($livewire) use ($tournament): array {
                        $resolvedTournament = self::resolveTournament($tournament, $livewire);

                        if (! $resolvedTournament || self::usesOfficialInstances($resolvedTournament)) {
                            return [];
                        }

                        return self::scoringRuleOptions($resolvedTournament, false);
                    })
                    ->searchable()
                    ->preload()
                    ->required(fn ($livewire): bool => ! self::usesOfficialInstances(self::resolveTournament($tournament, $livewire)))
                    ->visible(fn ($livewire): bool => ! self::usesOfficialInstances(self::resolveTournament($tournament, $livewire))),

                Select::make('tournament_slot_id')
                    ->label('Filtrar por horario')
                    ->placeholder('Todos los horarios')
                    ->options(fn ($livewire): array => self::slotOptions(
                        self::resolveTournament($tournament, $livewire)
                    ))
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('registration_ids', [])),

                CheckboxList::make('registration_ids')
                    ->label('Jugadores o parejas sin puntuación')
                    ->options(fn (Get $get, $livewire): array => self::registrationOptions(
                        self::resolveTournament($tournament, $livewire),
                        filled($get('tournament_slot_id')) ? (int) $get('tournament_slot_id') : null,
                    ))
                    ->required()
                    ->minItems(1)
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(2)
                    ->helperText('Marcá todas las inscripciones que obtuvieron la posición seleccionada.')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, $livewire) use ($tournament): void {
                $resolvedTournament = self::resolveTournament($tournament, $livewire);

                abort_unless($resolvedTournament, 404);
                abort_unless(
                    Auth::user()?->canGloballyOrForDiscipline(
                        'AssignTournamentScore',
                        $resolvedTournament->discipline_id,
                    ),
                    403,
                );

                $registrationIds = collect($data['registration_ids'] ?? [])
                    ->filter()
                    ->map(fn ($id): int => (int) $id)
                    ->values();

                $eligibleQuery = $resolvedTournament->registrations()
                    ->where('status', 'aprobado')
                    ->whereNull('points')
                    ->whereKey($registrationIds);

                if (filled($data['tournament_slot_id'] ?? null)) {
                    $eligibleQuery->where('tournament_slot_id', (int) $data['tournament_slot_id']);
                }

                $eligibleCount = $eligibleQuery->count();

                if ($eligibleCount !== $registrationIds->unique()->count()) {
                    throw ValidationException::withMessages([
                        'registration_ids' => 'Solo se pueden seleccionar inscripciones aprobadas, sin puntuación y pertenecientes al horario elegido.',
                    ]);
                }

                $items = $registrationIds
                    ->map(fn (int $registrationId): array => [
                        'registration_id' => $registrationId,
                        'penalty_points' => 0,
                    ])
                    ->all();

                app(\App\Services\TournamentScoringService::class)->assignBatch(
                    $resolvedTournament,
                    $items,
                    isset($data['tournament_instance_id']) ? (int) $data['tournament_instance_id'] : null,
                    $data['result_code'] ?? null,
                );

                if (self::usesOfficialInstances($resolvedTournament)) {
                    \App\Services\RankingService::syncGeneralRanking();
                }

                $livewire->dispatch('refreshTable');
            })
            ->successNotificationTitle('Puntajes asignados correctamente');
    }

    private static function resolveTournament($tournament, $livewire)
    {
        $resolvedTournament = $tournament
            ?? (method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord() : null);

        if ($resolvedTournament instanceof Collection) {
            $resolvedTournament = $resolvedTournament->first();
        }

        $resolvedTournament?->loadMissing('type');

        return $resolvedTournament;
    }

    private static function scoringRuleOptions($tournament, bool $officialInstances): array
    {
        return collect(app(\App\Services\TournamentScoringService::class)->getRules($tournament))
            ->filter(fn (array $rule): bool => $officialInstances
                ? ! empty($rule['tournament_instance_id'] ?? null)
                : filled($rule['code'] ?? null))
            ->mapWithKeys(function (array $rule) use ($officialInstances): array {
                $value = $officialInstances
                    ? (int) $rule['tournament_instance_id']
                    : (string) $rule['code'];
                $description = $rule['description'] ?? 'Sin descripción';
                $points = number_format((float) ($rule['points'] ?? 0), 2, ',', '.');

                return [$value => "{$description} — {$points} puntos"];
            })
            ->all();
    }

    private static function usesOfficialInstances($tournament): bool
    {
        return $tournament
            ? app(\App\Services\TournamentScoringService::class)->usesOfficialInstances($tournament)
            : false;
    }

    private static function slotOptions($tournament): array
    {
        if (! $tournament) {
            return [];
        }

        return $tournament->slots()
            ->where('is_active', true)
            ->with('tournamentModality.modality')
            ->orderBy('starts_at')
            ->get()
            ->mapWithKeys(function ($slot): array {
                $modality = $slot->tournamentModality?->modality?->name;
                $date = $slot->starts_at?->format('d/m/Y H:i');
                $description = implode(' · ', array_filter([$modality, $date]));
                $name = $slot->name ?: 'Horario #'.$slot->getKey();

                return [$slot->getKey() => $description !== '' ? "{$name} — {$description}" : $name];
            })
            ->all();
    }

    private static function assignableSlotOptions($tournament): array
    {
        if (! $tournament) {
            return [];
        }

        return $tournament->slots()
            ->where('is_active', true)
            ->with('tournamentModality.modality')
            ->orderBy('starts_at')
            ->get()
            ->reject(fn (TournamentSlot $slot): bool => self::isUnassignedSlotName($slot->name))
            ->mapWithKeys(function (TournamentSlot $slot): array {
                $modality = $slot->tournamentModality?->modality?->name;
                $date = $slot->starts_at?->format('d/m/Y H:i');
                $capacity = $slot->max_players === null
                    ? 'Sin límite de cupos'
                    : max(0, $slot->max_players - $slot->occupiedPlaces()).' cupos disponibles';
                $details = implode(' · ', array_filter([$modality, $date, $capacity]));
                $name = $slot->name ?: 'Horario #'.$slot->getKey();

                return [$slot->getKey() => $details !== '' ? "{$name} — {$details}" : $name];
            })
            ->all();
    }

    private static function unassignedRegistrationOptions($tournament, ?int $targetSlotId): array
    {
        if (! $tournament || ! $targetSlotId) {
            return [];
        }

        $targetSlot = $tournament->slots()
            ->where('is_active', true)
            ->find($targetSlotId);

        if (! $targetSlot || self::isUnassignedSlotName($targetSlot->name)) {
            return [];
        }

        return $tournament->registrations()
            ->where('tournament_modality_id', $targetSlot->tournament_modality_id)
            ->where(function (Builder $query): void {
                $query->whereNull('tournament_slot_id')
                    ->orWhereHas('slot', fn (Builder $slotQuery): Builder => $slotQuery
                        ->whereRaw('LOWER(TRIM(name)) = ?', ['sin asignar']));
            })
            ->with([
                'participants.player.category',
                'participants.player.club',
                'tournamentModality.modality',
                'slot',
            ])
            ->get()
            ->sortBy(fn (TournamentRegistration $registration): string => $registration->participant_names)
            ->mapWithKeys(function (TournamentRegistration $registration): array {
                $clubs = $registration->participants
                    ->pluck('player.club.name')
                    ->filter()
                    ->unique()
                    ->implode(' / ');
                $categories = $registration->participants
                    ->pluck('player.category.code')
                    ->filter()
                    ->unique()
                    ->implode(' / ');
                $modality = $registration->tournamentModality?->modality?->name;
                $currentSlot = $registration->slot?->name ?: 'Sin asignar';
                $label = $registration->participant_names ?: "Inscripción #{$registration->getKey()}";

                $row = new HtmlString(sprintf(
                    '<div class="fab-slot-assignment-row"><strong>%s</strong><span data-label="Modalidad">%s</span><span data-label="Categoría">%s</span><span data-label="Club">%s</span><span data-label="Horario actual">%s</span></div>',
                    e($label),
                    e($modality ?: '—'),
                    e($categories ?: '—'),
                    e($clubs ?: '—'),
                    e($currentSlot),
                ));

                return [
                    $registration->getKey() => $row,
                ];
            })
            ->all();
    }

    private static function isUnassignedSlotName(?string $name): bool
    {
        return mb_strtolower(trim((string) $name)) === 'sin asignar';
    }

    private static function registrationOptions($tournament, ?int $slotId = null): array
    {
        if (! $tournament) {
            return [];
        }

        $query = $tournament->registrations()
            ->where('status', 'aprobado')
            ->whereNull('points')
            ->with([
                'participants.player.category',
                'tournamentModality.modality',
                'slot',
            ]);

        if ($slotId) {
            $query->where('tournament_slot_id', $slotId);
        }

        return $query->get()
            ->sortBy(fn (TournamentRegistration $registration): string => $registration->participant_names)
            ->mapWithKeys(function (TournamentRegistration $registration): array {
                $categories = $registration->participants
                    ->pluck('player.category.code')
                    ->filter()
                    ->unique()
                    ->implode(' / ');
                $modality = $registration->tournamentModality?->modality?->name;
                $slot = $registration->slot?->name;
                $details = implode(' · ', array_filter([$modality, $categories, $slot]));
                $label = $registration->participant_names ?: "Inscripción #{$registration->getKey()}";

                return [
                    $registration->getKey() => $details !== '' ? "{$label} — {$details}" : $label,
                ];
            })
            ->all();
    }
}
