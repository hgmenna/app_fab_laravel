<?php

namespace App\Filament\Resources\Federations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;

class FederationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('short_name')
                    ->label('Sigla')
                    ->alignCenter()
                    ->sortable()
                    ->searchable(),
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->alignCenter()
                    ->square(50),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('verClubes')
                    ->label('Clubes')
                    ->icon('heroicon-m-building-office-2')
                    ->color('info')
                    ->modalHeading('Listado de Clubes')
                    ->modalWidth('5xl')
                    ->modalContent(fn ($record) => view(
                        'federaciones.table.table-clubes', // Nombre de vista corregido
                        [
                            'record' => $record->load(['states.cities.clubs' => function($query) {
                                $query->withCount('players'); 
                            }])
                        ]
                    ))
                    ->modalSubmitAction(false),
                ViewAction::make()
                    ->label('Ver')
                    ->iconButton(),
                EditAction::make()
                    ->label('Editar')
                    ->iconButton(),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
