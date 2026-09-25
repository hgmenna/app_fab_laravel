<?php

namespace App\Filament\Resources\TournamentRegulationSettings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TournamentRegulationSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('discipline_id')->relationship('discipline', 'name')->label('Disciplina')
                ->unique(ignoreRecord: true)->searchable()->preload()->required(),
            Toggle::make('enabled')->label('Control habilitado')->default(true)->required(),
            TextInput::make('minimum_distance_km')->label('Distancia mínima entre torneos no oficiales (km)')
                ->numeric()->minValue(0)->step(0.1)
                ->required(fn (Get $get): bool => (bool) $get('check_non_official_distance')),
            Toggle::make('check_non_official_distance')->label('Controlar distancia entre torneos no oficiales')
                ->default(true)->live()->required(),
            Toggle::make('block_non_official_against_official_same_state')
                ->label('Bloquear no oficial frente a oficial de la misma provincia')
                ->helperText('Se aplica cuando las fechas y al menos una categoría coinciden.')
                ->default(true)->required(),
            Toggle::make('check_club_category_quota')
                ->label('Limitar torneos no oficiales por club y categoría')
                ->helperText('Controla cuántos torneos no oficiales todavía no finalizados puede tener un club para una misma categoría dentro del período configurado.')
                ->default(false)
                ->live()
                ->required(),
            TextInput::make('max_non_official_tournaments_per_category')
                ->label('Cantidad máxima de torneos activos por categoría')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->required(fn (Get $get): bool => (bool) $get('check_club_category_quota'))
                ->visible(fn (Get $get): bool => (bool) $get('check_club_category_quota')),
            TextInput::make('club_category_period_months')
                ->label('Período de control (meses)')
                ->helperText('Ejemplo: máximo 2 torneos activos de una categoría durante cualquier período de 6 meses. Al finalizar uno, libera un cupo.')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->required(fn (Get $get): bool => (bool) $get('check_club_category_quota'))
                ->visible(fn (Get $get): bool => (bool) $get('check_club_category_quota')),
            DatePicker::make('effective_from')->label('Vigente desde'),
            DatePicker::make('effective_until')->label('Vigente hasta')->afterOrEqual('effective_from'),
            Textarea::make('notes')->label('Observaciones')->columnSpanFull(),
        ])->columns(2);
    }
}
