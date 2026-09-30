<?php

namespace App\Filament\Resources\TournamentTypes\Tables;

use App\Filament\Actions\GlobalActionGroup;
use App\Filament\Actions\GlobalDeleteAction;
use App\Filament\Actions\GlobalEditAction;
use App\Filament\Actions\GlobalViewAction;
use App\Services\AdminNotifier;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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
                SelectFilter::make('discipline_id')
                    ->label('Disciplina')
                    ->relationship('discipline', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                TernaryFilter::make('has_handicap')
                    ->label('Hándicap')
                    ->placeholder('Todos')
                    ->trueLabel('Con hándicap')
                    ->falseLabel('Sin hándicap'),
                TernaryFilter::make('is_official')
                    ->label('Carácter')
                    ->placeholder('Todos')
                    ->trueLabel('Oficiales')
                    ->falseLabel('No oficiales'),
                TernaryFilter::make('exclusive_during_dates')
                    ->label('Exclusividad')
                    ->placeholder('Todos')
                    ->trueLabel('Exclusivos')
                    ->falseLabel('No exclusivos'),
                TernaryFilter::make('affects_ranking')
                    ->label('Afecta al ranking')
                    ->placeholder('Todos')
                    ->trueLabel('Sí')
                    ->falseLabel('No'),
                TernaryFilter::make('assigns_points')
                    ->label('Asigna puntos')
                    ->placeholder('Todos')
                    ->trueLabel('Sí')
                    ->falseLabel('No'),
                SelectFilter::make('scoring_method')
                    ->label('Método de puntuación')
                    ->options([
                        'position' => 'Posición o instancia',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->recordActions([
                GlobalActionGroup::make([
                    GlobalViewAction::make(),
                    GlobalEditAction::make(),
                    AdminNotifier::notifyAction(GlobalDeleteAction::make(), null, 'eliminó', ['name'], 'Tipos de torneo'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    AdminNotifier::notifyBulkAction(DeleteBulkAction::make(), 'eliminó', ['name'], 'Tipos de torneo'),
                    AdminNotifier::notifyBulkAction(ForceDeleteBulkAction::make(), 'eliminó definitivamente', ['name'], 'Tipos de torneo'),
                    AdminNotifier::notifyBulkAction(RestoreBulkAction::make(), 'restauró', ['name'], 'Tipos de torneo'),
                ]),
            ]);
    }
}
