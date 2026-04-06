<?php

namespace App\Filament\Resources\Players\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use App\Filament\Resources\Players\PlayerResource;
use App\Models\GeneralRanking;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
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
                ToggleColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->disabled(fn () => !Auth::user()?->can('EditField')),
                IconColumn::make('is_enabled_to_compete')
                    ->label('Habilitado')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckBadge)
                    ->falseIcon(Heroicon::OutlinedXMark),

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
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->default(true),
                TernaryFilter::make('is_enabled_to_compete')
                    ->label('Afiliado año actual')
                    ->placeholder('Todos')
                    ->trueLabel('Habilitados')
                    ->falseLabel('Inhabilitados'),
            ])
            ->recordActions([
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    GlobalDeleteAction::make(),
                    Action::make('verTorneos')
                        ->label('Ver Torneos')
                        ->icon(Heroicon::Trophy)
                        ->color('info')
                        ->modalHeading('Historial de Torneos')
                        ->modalWidth('5xl')
                        // SOLUCIÓN: Usar el helper view() de Laravel y pasar el registro
                        ->modalContent(fn ($record) => view(
                            'jugadores.table.detalles-torneo',
                            ['record' => $record]
                        ))
                        ->modalSubmitAction(false),

                    PlayerResource::payMemberShipAction(),
                    
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('payMembership')
                    ->label('Afiliación')
                    ->icon('heroicon-o-credit-card')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn () => (Auth::user()?->can('PayMembership') ?? false))
                    ->action(fn ($records) => PlayerResource::processPayMembership($records)),
            ])
            ->headerActions([
                PlayerResource::exportarPdf(),
                PlayerResource::importPlayers(),
            ]);
    }

}
