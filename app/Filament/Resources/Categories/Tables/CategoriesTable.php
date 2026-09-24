<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('discipline.name')
                    ->label('Disciplina')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order')
                    ->label('Orden')
                    ->sortable(),
                TextColumn::make('players_count')
                    ->label('Jugadores')
                    ->counts('players'),
            ])
            ->defaultSort('order')
            ->filters([
                SelectFilter::make('discipline_id')
                    ->relationship('discipline', 'name')
                    ->label('Disciplina'),
            ])
            ->recordActions([
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    GlobalDeleteAction::make(),
                ]),
            ]);
    }
}
