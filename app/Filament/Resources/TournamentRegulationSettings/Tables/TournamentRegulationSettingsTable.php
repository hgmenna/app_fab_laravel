<?php
namespace App\Filament\Resources\TournamentRegulationSettings\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TournamentRegulationSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('discipline.name')->label('Disciplina')->searchable()->sortable(),
            TextColumn::make('minimum_distance_km')->label('Distancia mínima')->suffix(' km'),
            IconColumn::make('check_non_official_distance')->label('Control de distancia')->boolean(),
            IconColumn::make('block_non_official_against_official_same_state')->label('Oficial por provincia')->boolean(),
            IconColumn::make('enabled')->label('Activo')->boolean(),
            TextColumn::make('effective_from')->label('Desde')->date('d/m/Y'),
            TextColumn::make('effective_until')->label('Hasta')->date('d/m/Y'),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
