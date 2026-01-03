<?php

namespace App\Filament\Resources\Countries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('iso3')
                    ->searchable(),
                TextColumn::make('phonecode')
                    ->label('Código Telefónico')
                    ->numeric()
                    ->searchable(),
                TextColumn::make('capital')
                    ->searchable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('region')
                    ->searchable(),
                TextColumn::make('subregion')
                    ->searchable(),
                
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
