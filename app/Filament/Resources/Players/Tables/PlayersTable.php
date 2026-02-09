<?php

namespace App\Filament\Resources\Players\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Components\View as SchemaView;
// Contrato de Laravel para el hint de la función
use Illuminate\Contracts\View\View as LaravelView;

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
                TrashedFilter::make(),
                SelectFilter::make('category_id')
                    ->relationship('category', 'name')
            ])
            ->recordActions([
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
