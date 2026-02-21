<?php

namespace App\Filament\Resources\TournamentRegistrations\Tables;

use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\ImageColumn;
use App\Models\TournamentInstance;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use App\Models\TournamentRegistration;
use App\Services\RankingService;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\CreateAction;
use App\Services\AdminNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use App\Models\GeneralRanking;

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

                ImageColumn::make('payment_file')
                    ->label('Pago')
                    ->square(60),

            ])
            ->filters([
                SelectFilter::make('tournament_slot_id')
                    ->label('Horario')
                    ->options(function ($livewire) use ($tournament) {
                        // Buscamos el torneo: ya sea el pasado por parámetro o el de la página actual
                        $t = $tournament ?? (method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord() : null);
                        
                        // Si hay torneo, devolvemos sus slots; si no, un array vacío
                        return $t ? $t->slots->pluck('name', 'id') : [];
                }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Inscribirse al torneo')
                    ->icon('heroicon-o-plus')
                    ->modal()
                    ->modalHeading('Nueva Inscripción')
                    ->disabled(function ($livewire) use ($tournament) {
                        // 1. Resolvemos el torneo de forma dinámica [1, 2]
                        $t = $tournament ?? (method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord() : null);
                        
                        // Si no hay torneo (vista general), no se permite crear sin elegir uno primero en el form
                        if (!$t) return false; 

                        // 2. Validamos si las inscripciones están abiertas [104 de tu error]
                        $now = now();
                        $isOpen = ($t->registration_open_at <= $now && $t->registration_close_at >= $now);
                        
                        return !$isOpen;
                    })
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
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

                        // Pasar el slotId al método de exportación
                        return TournamentRegistrationResource::exportRegistrationsToPdf($tournament, $slotId);
                    }
                ),                                                                                                                                                 
            ])
            ->recordActions([
                ViewAction::make()->iconButton(),
                EditAction::make()->iconButton()->visible(fn () => Auth::user()?->can('EditField'))
                    ->after(function (Model $record) {
                        $tournamentName = $record->tournament?->name ?? 'el torneo';

                        AdminNotifier::send(
                            null, 
                            $record, 
                            'modificó la inscripción de', 
                            ['player.last_name', 'player.first_name'], 
                            "el torneo {$tournamentName}"
                        );
                    }
                ),
                DeleteAction::make()->iconButton()->visible(fn () => Auth::user()?->can('EditField')),
                Action::make('asignarInstancia')
                    ->label('Asignar Posicion')
                    ->visible(fn (?TournamentRegistration $record) => 
                        Auth::user()?->can('EditField') && 
                        $record?->tournament?->start_date < now()
                    )
                    ->disabled(fn (TournamentRegistration $record) => 
                        $record->tournament?->start_date > now()
                    )
                    ->modalHeading('Asignar Posicion y calcular puntos')
                    ->form([
                        Select::make('tournament_instance_id')
                            ->label('Instancia')
                            ->options(
                                TournamentInstance::pluck('description', 'id')->toArray()
                            )
                            ->nullable(), // permitir null

                        TextInput::make('penalty_points')
                            ->label('Penalizacion')
                            ->numeric()
                            ->default(0)
                            ->helperText('Puntos a descontar por inasistencia en Master/1ra.'),
                    ])
                    ->action(function (array $data, TournamentRegistration $record) {

                        // Guardar instancia (puede ser null)
                        $record->tournament_instance_id = $data['tournament_instance_id'] ?? null;
                        $record->penalty_points = $data['penalty_points'] ?? 0; 
                        $record->save();

                        // Recargar relaciones para evitar usar la instancia vieja en memoria
                        $record->refresh();

                        // Si no hay instancia, puntos = null
                        if (! $record->tournament_instance_id) {
                            $record->points = null;
                        } else {
                            $record->points = $record->calculatePoints();

                        }

                        $record->save();
                        RankingService::syncGeneralRanking();
                    }
                )
                ->color('primary'),
            ]);
    }
}
