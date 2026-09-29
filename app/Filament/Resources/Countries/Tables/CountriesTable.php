<?php

namespace App\Filament\Resources\Countries\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use App\Services\AdminNotifier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
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
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    AdminNotifier::notifyAction(GlobalDeleteAction::make(), null, 'eliminó', ['name'], 'Países'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    AdminNotifier::notifyBulkAction(DeleteBulkAction::make(), 'eliminó', ['name'], 'Países'),
                ]),
            ]);
    }
}
