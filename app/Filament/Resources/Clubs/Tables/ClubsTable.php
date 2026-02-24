<?php

namespace App\Filament\Resources\Clubs\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use App\Filament\Resources\Clubs\ClubResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClubsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading('Gestión de Clubes')
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->square(40),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->limit(15)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('Domicilio')
                    ->limit(15)
                    ->searchable(),
                TextColumn::make('city.name')
                    ->label('Ciudad')
                    ->limit(15)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('city.state.federation.short_name')
                    ->label('Federación')
                    ->sortable()
                    ->searchable(),
                 TextColumn::make('players_count')
                    ->label('Afil')
                    ->counts('players')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo'),
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
                SelectFilter::make('federation_id')
                    ->label('Federación')
                    ->relationship('city.state.federation', 'name'),
                SelectFilter::make('is_active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activo',
                        0 => 'Inactivo',
                    ]),
            ])
            ->recordActions([
                GlobalActionGroup::make([
                    ClubResource::viewAfiliatesAction(),
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    GlobalDeleteAction::make(),
                ]),
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
                        return ClubResource::exportToPdf($records, 'Listado de Clubes');
                    }),
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
