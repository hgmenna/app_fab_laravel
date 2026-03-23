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
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TournamentRegistrationsTable
{
    protected static string $resource = TournamentRegistrationResource::class;

    public static function configure(Table $table, $tournament): Table
    {
        return $table
            ->columns([
                 TextColumn::make('player.last_name')
                    ->label('Apellido')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('player.first_name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('player.club.name')
                    ->label('Club')
                    ->limit(15)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('player.category.name')
                    ->label('Cat')
                    ->searchable()
                    ->sortable(),

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
                    ->visible(fn () => Auth::user()?->can('EditField')
                ),

                TextColumn::make('points')
                    ->label('Puntos')
                    ->visible(fn () => Auth::user()?->can('EditField'))
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
                        if (!$value) {
                            return; // 🔥 evita 500 cuando no hay filtro
                        }

                        $query->whereHas('player.category', function ($q) use ($value) {
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
                        return $t ? $t->slots->pluck('name', 'id') : [];
                }),
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
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Inscribirse al torneo')
                    ->icon('heroicon-o-plus')
                    ->modal()
                    ->modalHeading('Nueva Inscripción')
                    ->disabled(function ($livewire) use ($tournament) {

                        $user = Auth::user();
                        if ($user?->name === 'super-admin') return false;
                        
                        // 1. Resolvemos el torneo de forma dinámica [1, 2]
                        $t = $tournament ?? (method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord() : null);
                        
                        // Si no hay torneo (vista general), no se permite crear sin elegir uno primero en el form
                        if (!$t) return false; 

                        // 2. Validamos si las inscripciones están abiertas [104 de tu error]
                        $now = now();
                        $isOpen = ($t->registration_open_at <= $now && $t->registration_close_at >= $now);
                        
                        return !$isOpen;
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
                        ->visible(fn () => Auth::user()?->can('EditField'))
                        ->after(function (Model $record) {
                            $tournamentName = $record->tournament?->name ?? 'el torneo';

                            $emailDestino = Auth::user()->email ?? 'notificaciones@federacionargentinadebillar.org';
                            Mail::to($emailDestino)->send(new TournamentRegistrationNotification($record, 'Actualizacion de inscripcion'));
    
                            AdminNotifier::send(
                                null, 
                                $record, 
                                'modificó la inscripción de', 
                                ['player.last_name', 'player.first_name'], 
                                "el torneo {$tournamentName}"
                            );
                        }
                    ),
                    GlobalDeleteAction::make()
                        ->visible(fn () => Auth::user()?->can('EditField'))
                        ->after(function (Model $record) {
                            $tournamentName = $record->tournament?->name ?? 'el torneo';
    
                            AdminNotifier::send(
                                null, 
                                $record, 
                                'eliminó la inscripción de', 
                                ['player.last_name', 'player.first_name'], 
                                "el torneo {$tournamentName}"
                            );
                        })
                        ->before(function (Model $record){
                            $emailDestino = Auth::user()->email ?? 'notificaciones@federacionargentinadebillar.org';
                            Mail::to($emailDestino)->send(
                                new TournamentRegistrationNotification($record, 'Inscripción eliminada')
                            );
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
                            $record->load(['slot', 'player.club', 'player.category']);
                            $mailDestino = Auth::user()->email ?? 'notificaciones@federacionargentinadebillar.org';
                            Mail::to($mailDestino)
                                ->send(new TournamentRegistrationNotification($record, 'Actualizacion de estado de inscripcion'));

                            $tournamentName = $record->tournament?->name ?? 'el torneo';

                            AdminNotifier::send(
                                null, 
                                $record, 
                                'Actualizó estado de la inscripción de', 
                                ['player.last_name', 'player.first_name'], 
                                "el torneo {$tournamentName}"
                            );
                        })
                        ->visible(fn () => (Auth::user()?->can('UpdateStatusTournament') ?? false)
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
}
