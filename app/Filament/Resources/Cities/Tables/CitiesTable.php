<?php

namespace App\Filament\Resources\Cities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('state.name')
                    ->label('Provincia')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('state.country.name')
                    ->label('Pais')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('postal_code')
                    ->label('Codigo Postal')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('latitude')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('longitude')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
                SelectFilter::make('state_id')
                    ->relationship('state', 'name')
                    ->label('Provincia'),
                SelectFilter::make('state.country.name')
                    ->relationship('state.country', 'name')
                    ->label('Pais'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
