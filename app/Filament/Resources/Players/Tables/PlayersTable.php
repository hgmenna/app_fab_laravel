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
                        return \App\Models\GeneralRanking::where('first_name', $record->first_name)
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
