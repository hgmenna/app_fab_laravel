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
            DatePicker::make('effective_from')->label('Vigente desde'),
            DatePicker::make('effective_until')->label('Vigente hasta')->afterOrEqual('effective_from'),
            Textarea::make('notes')->label('Observaciones')->columnSpanFull(),
        ])->columns(2);
    }
}
