<?php

namespace App\Filament\Resources\States\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'Asc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('country.name')
                    ->label('Pais')
                    ->searchable(),
                TextColumn::make('federation.short_name')
                    ->label('Federacion')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('latitude')
                    ->label('Latitud')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('longitude')
                    ->label('Longitud')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //php
            ])
            ->recordActions([
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    GlobalDeleteAction::make(),
                ])
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
