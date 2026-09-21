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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
                    ->disabled(fn () => ! Auth::user()?->hasPermissionTo('EditField')),
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
                    PlayerResource::changeCategoryAction(),
                    PlayerResource::viewCategoryHistoryAction(),
                    Action::make('verDesempeno')
                        ->label('Desempeño en torneos')
                        ->icon(Heroicon::Trophy)
                        ->color('info')
                        ->url(fn ($record): string => PlayerResource::getUrl('performance', ['record' => $record])),

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

                BulkAction::make('desactivar')
                    ->label('Desactivar seleccionados')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn () => Auth::user()?->hasPermissionTo('EditField'))
                    ->action(function (Collection $records) {
                        $records->each->update(['is_active' => false]);
                    }),

                BulkAction::make('activar')
                    ->label('Activar seleccionados')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->visible(fn () => Auth::user()?->hasPermissionTo('EditField'))
                    ->action(function (Collection $records) {
                        $records->each->update(['is_active' => true]);
                    }),

            ])
            ->headerActions([
                Action::make('performanceFiltered')
                    ->label('Desempeño de jugadores filtrados')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->action(function ($livewire) {
                        // El filtro y la búsqueda actuales se evalúan sobre TODOS los jugadores,
                        // incluyendo los que no aparecen en la página de resultados visible.
                        $ids = $livewire->getFilteredTableQuery()
                            ->pluck('players.id')
                            ->map(fn ($id): int => (int) $id)
                            ->unique()
                            ->values()
                            ->all();

                        $report = (string) Str::uuid();
                        session()->put("player-performance-reports.{$report}", [
                            'user_id' => Auth::id(),
                            'player_ids' => $ids,
                        ]);

                        return redirect(PlayerResource::getUrl('performance-all', ['report' => $report]));
                    }),
                 Action::make('categoryChangesReport')
                    ->label('Cambios de categoría')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->url(fn (): string => PlayerResource::getUrl('category-changes-report')),
                PlayerResource::exportarPdf(),
                PlayerResource::importPlayers(),
            ]);
    }

}
