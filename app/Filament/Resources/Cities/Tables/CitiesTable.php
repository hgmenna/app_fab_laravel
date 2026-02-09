<?php

namespace App\Filament\Resources\Cities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                TextColumn::make('state.federation.short_name')
                    ->label('Fedracion')
                    ->searchable(),
                TextColumn::make('state.country.name')
                    ->label('Pais')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('postal_code')
                    ->label('Codigo Postal')
                    ->searchable()
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
                EditAction::make()->iconButton(),
                ViewAction::make()->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
