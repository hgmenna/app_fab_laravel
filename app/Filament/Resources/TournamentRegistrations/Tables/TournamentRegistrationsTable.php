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
use App\Models\TournamentRegistration;
use App\Services\AdminNotifier;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Facades\Mail;
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

    private static function bulkScoringAction($tournament): Action
    {
        return Action::make('asignarPuntajesMasivos')
            ->label('Asignar puntajes')
            ->icon('heroicon-o-list-bullet')
            ->color('primary')
            ->modalHeading('Asignación masiva de puntajes')
            ->modalDescription('Elegí una posición y agregá todos los jugadores o parejas que recibirán el mismo puntaje.')
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

                        if (! $resolvedTournament?->type?->affects_ranking) {
                            return [];
                        }

                        return self::scoringRuleOptions($resolvedTournament, true);
                    })
                    ->searchable()
                    ->preload()
                    ->required(fn ($livewire): bool => (bool) self::resolveTournament($tournament, $livewire)?->type?->affects_ranking)
                    ->visible(fn ($livewire): bool => (bool) self::resolveTournament($tournament, $livewire)?->type?->affects_ranking),

                Select::make('result_code')
                    ->label('Resultado')
                    ->options(function ($livewire) use ($tournament): array {
                        $resolvedTournament = self::resolveTournament($tournament, $livewire);

                        if (! $resolvedTournament || $resolvedTournament->type?->affects_ranking) {
                            return [];
                        }

                        return self::scoringRuleOptions($resolvedTournament, false);
                    })
                    ->searchable()
                    ->preload()
                    ->required(fn ($livewire): bool => ! (bool) self::resolveTournament($tournament, $livewire)?->type?->affects_ranking)
                    ->visible(fn ($livewire): bool => ! (bool) self::resolveTournament($tournament, $livewire)?->type?->affects_ranking),

                Repeater::make('items')
                    ->label('Jugadores o parejas')
                    ->helperText('Cada inscripción puede aparecer una sola vez. Usá la penalización únicamente cuando corresponda.')
                    ->schema([
                        Select::make('registration_id')
                            ->label('Jugador / pareja')
                            ->options(fn ($livewire): array => self::registrationOptions(
                                self::resolveTournament($tournament, $livewire)
                            ))
                            ->required()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->searchable()
                            ->preload(),
                        TextInput::make('penalty_points')
                            ->label('Penalización')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ])
                    ->table([
                        TableColumn::make('Jugador / pareja')->markAsRequired(),
                        TableColumn::make('Penalización')->width('12rem'),
                    ])
                    ->defaultItems(1)
                    ->minItems(1)
                    ->reorderable(false)
                    ->addActionLabel('Agregar jugador o pareja')
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

                $items = array_values($data['items'] ?? []);
                $registrationIds = collect($items)
                    ->pluck('registration_id')
                    ->filter()
                    ->map(fn ($id): int => (int) $id)
                    ->values();

                $approvedCount = $resolvedTournament->registrations()
                    ->where('status', 'aprobado')
                    ->whereKey($registrationIds)
                    ->count();

                if ($approvedCount !== $registrationIds->unique()->count()) {
                    throw ValidationException::withMessages([
                        'items' => 'Solo se pueden puntuar inscripciones aprobadas de este torneo.',
                    ]);
                }

                app(\App\Services\TournamentScoringService::class)->assignBatch(
                    $resolvedTournament,
                    $items,
                    isset($data['tournament_instance_id']) ? (int) $data['tournament_instance_id'] : null,
                    $data['result_code'] ?? null,
                );

                if ($resolvedTournament->type?->affects_ranking) {
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

    private static function registrationOptions($tournament): array
    {
        if (! $tournament) {
            return [];
        }

        return $tournament->registrations()
            ->where('status', 'aprobado')
            ->with([
                'participants.player.category',
                'tournamentModality.modality',
            ])
            ->get()
            ->sortBy(fn (TournamentRegistration $registration): string => $registration->participant_names)
            ->mapWithKeys(function (TournamentRegistration $registration): array {
                $categories = $registration->participants
                    ->pluck('player.category.code')
                    ->filter()
                    ->unique()
                    ->implode(' / ');
                $modality = $registration->tournamentModality?->modality?->name;
                $details = implode(' · ', array_filter([$modality, $categories]));
                $label = $registration->participant_names ?: "Inscripción #{$registration->getKey()}";

                return [
                    $registration->getKey() => $details !== '' ? "{$label} — {$details}" : $label,
                ];
            })
            ->all();
    }
}
