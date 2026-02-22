<?php

namespace App\Filament\Resources\Players\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
// Contrato de Laravel para el hint de la función
use App\Filament\Resources\Players\PlayerResource;
use App\Services\AdminNotifier;
use App\Models\Membership;
use Filament\Notifications\Notification;
use App\Models\GeneralRanking;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Auth;

class PlayersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('last_name', 'asc')
            ->columns([
                
                TextColumn::make('last_name')
                    ->label('Apellido')
                    ->alignCenter()
                    ->searchable(),
                TextColumn::make('first_name')
                    ->label('Nombre')
                    ->alignCenter()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('club.name')
                    ->label('Club')
                    ->limit(15)
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.code')
                    ->label('Cat')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('club.city.state.federation.short_name')
                    ->label('Federacion')
                    ->alignCenter()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cant_torneos')
                    ->label('C/T')
                    ->alignCenter()
                    ->sortable(false)
                    ->numeric()
                    ->getStateUsing(function ($record): string {
                        // 1. Obtenemos los registros y aplicamos el mismo filtro que en el Blade
                        $registros = ($record->registrations ?? collect())->filter(function ($registro) {
                            return $registro->tournament?->end_date && $registro->tournament->end_date->isPast();
                        });

                        // 2. Si no hay torneos disputados, devolvemos 0
                        if ($registros->isEmpty()) {
                            return '0';
                        }

                        return $registros->count();
                    }),
                 TextColumn::make('promedio_puntos')
                    ->label('Prom Ptos')
                    ->alignCenter()
                    ->sortable(false)
                    ->getStateUsing(function ($record): string {
                        // 1. Obtenemos los registros y aplicamos el mismo filtro que en el Blade
                        $registros = ($record->registrations ?? collect())->filter(function ($registro) {
                            return $registro->tournament?->end_date && $registro->tournament->end_date->isPast();
                        });

                        // 2. Si no hay torneos disputados, devolvemos 0
                        if ($registros->isEmpty()) {
                            return '0.00';
                        }

                        // 3. Calculamos el promedio (o el porcentaje si tienes un campo de puntos máximos)
                        $promedio = $registros->avg('points'); 

                        return number_format($promedio, 2);
                    }),
                // Columna para el Ranking General (RG)
                TextColumn::make('ranking_rg')
                    ->label('RG')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return GeneralRanking::where('first_name', $record->first_name)
                            ->where('last_name', $record->last_name)
                            ->value('RG') ?? '-';
                    }),

                // Columna para la Categoría del Ranking
                TextColumn::make('ranking_category')
                    ->label('C/R')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        return \App\Models\GeneralRanking::where('first_name', $record->first_name)
                            ->where('last_name', $record->last_name)
                            ->value('category') ?? '-';
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('Categoria')
                    ->multiple(),
                SelectFilter::make('federation_id')
                    ->relationship('club.city.state.federation', 'short_name')
                    ->label('Federacion')
                    ->multiple(),
            ])
            ->recordActions([
                    ViewAction::make()->iconButton(),
                    EditAction::make()->iconButton(),
                    DeleteAction::make()->iconButton(),
                Action::make('verTorneos')
                    ->label('Torneos')
                    ->icon('heroicon-m-trophy')
                    ->color('info')
                    ->modalHeading('Historial de Torneos')
                    ->modalWidth('5xl')
                    // SOLUCIÓN: Usar el helper view() de Laravel y pasar el registro
                    ->modalContent(fn ($record) => view(
                        'jugadores.table.detalles-torneo',
                        ['record' => $record]
                    ))
                    ->modalSubmitAction(false),
                Action::make('payMembership')
                    ->label('Afiliacion')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->visible(fn () => Auth::user()->can('PayMembership'))
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Pago de Membresía')
                    ->disabled(fn ($record) => $record->is_enabled_to_compete)
                    ->action(function ($record, $livewire) {
                        try {
                            // 1. Validar existencia de membresía activa para el año actual
                            $activeMembership = Membership::where('active', true)
                                ->where('year', now()->year)
                                ->first();

                            if (!$activeMembership) {
                                Notification::make()
                                    ->title('Configuración faltante')
                                    ->body('No hay una membresía activa definida para este año.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // 2. Crear o recuperar la afiliación del jugador
                            $playerMembership = $record->memberships()->firstOrCreate(
                                ['membership_id' => $activeMembership->id],
                                [
                                    'club_id' => $record->club_id,
                                    'amount_due' => $activeMembership->amount,
                                    'amount_paid' => 0,
                                    'status' => 'pending',
                                ]
                            );

                            // 3. Registrar el pago (Preparado para external_reference de Mercado Pago)
                            $payment = $playerMembership->payments()->create([
                                'payer_type' => get_class($record),
                                'payer_id' => $record->id,
                                'amount' => $activeMembership->amount,
                                'method' => 'manual', 
                                'status' => 'pending',
                                'external_reference' => 'MANUAL-' . uniqid(),
                            ]);

                            // 4. Aprobar pago (dispara refreshStatus() en PlayerMembership) [5, 6]
                            $payment->approve();

                            // 5. Actualizar estado maestro del jugador [7]
                            $record->update(['is_enabled_to_compete' => true]);

                            // 6. NOTIFICACIÓN CENTRALIZADA (Uso del nuevo servicio) [3, 4]
                            AdminNotifier::send(
                                pageInstance: null, // Al ser acción de tabla no requiere instancia de página
                                record: $record,
                                operation: 'habilitó para competir (Pago Membresía)',
                                displayFields: ['last_name', 'first_name'], // Resuelve automáticamente el nombre [4]
                                customResourceName: 'jugador'
                            );

                            Notification::make()
                                ->title('Proceso completado')
                                ->success()
                                ->body("El jugador {$record->full_name} ya puede competir.")
                                ->send();

                        } catch (\Throwable $e) {
                            // Notificar error técnico a los administradores [1, 8]
                            AdminNotifier::sendException($e);
                            
                            Notification::make()
                                ->title('Error en el proceso')
                                ->danger()
                                ->send();
                        }
                    }
                ),
            ])
            ->headerActions([
            Action::make('descargarPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function ($livewire) {
                    // Obtenemos los registros filtrados desde el componente Livewire
                    $records = $livewire->getFilteredTableQuery()->get();
                    
                    // Invocamos el método estático del Resource
                    return PlayerResource::exportToPdf($records, 'Listado de Jugadores');
                }),
            ]);
    }

}
