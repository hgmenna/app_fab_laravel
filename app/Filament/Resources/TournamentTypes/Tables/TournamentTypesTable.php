<?php

namespace App\Filament\Resources\TournamentTypes\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TournamentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('discipline.name')
                    ->label('Disciplina')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('participation_mode')
                    ->label('Mod')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pairs' => 'Par',
                        default => 'Ind',
                    })
                    ->badge(),
                IconColumn::make('has_handicap')
                    ->label('Hánd')
                    ->boolean(),
                IconColumn::make('is_official')
                    ->label('Oficial')
                    ->boolean(),
                IconColumn::make('exclusive_during_dates')
                    ->label('Exclusivo')
                    ->boolean(),
                IconColumn::make('affects_ranking')
                    ->label('Af ran')
                    ->boolean(),
                IconColumn::make('assigns_points')
                    ->label('As. ptos')
                    ->boolean(),
                TextColumn::make('scoring_method')
                    ->label('Método')
                    ->formatStateUsing(
                        fn (?string $state): string => match ($state) {
                            'position' => 'Posición o instancia',
                            default => 'Sin configurar',
                        }
                    )
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ])
            ->recordActions([
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    GlobalDeleteAction::make(),
                ]),
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
