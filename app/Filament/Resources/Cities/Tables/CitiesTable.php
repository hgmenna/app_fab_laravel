<?php

namespace App\Filament\Resources\Cities\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
                    ->label('Nombre')
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
