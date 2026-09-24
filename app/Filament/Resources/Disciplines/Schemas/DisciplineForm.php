<?php

namespace App\Filament\Resources\Disciplines\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

class DisciplineForm
{
    public static function getFormSchema(): array
    {
            return [
                TextInput::make('name')
                    ->required()
                    ->label('Nombre'),
                TextInput::make('code')
                    ->default(null)
                    ->label('Abreviatura')
                    ->string(),
                TextInput::make('short_name')
                    ->default(null)
                    ->label('Nombre corto'),
                Textarea::make('description')
                    ->label('Descripción')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('scoring_rules')
                    ->label('Reglas de puntuación')
                    ->default(null)
                    ->columnSpanFull(),
                Toggle::make('active')
                    ->label('Activo')
                    ->required(),
                Select::make('affiliation_mode')
                    ->label('Modalidad de afiliación')
                    ->options([
                        'provincial' => 'Por federación provincial del club',
                        'direct' => 'Directa a federación nacional',
                    ])
                    ->default('provincial')
                    ->required()
                    ->live(),
                Select::make('direct_federation_id')
                    ->relationship('directFederation', 'name')
                    ->label('Federación de afiliación directa')
                    ->helperText('Seleccioná Federación Argentina para las disciplinas con afiliación nacional directa.')
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => $get('affiliation_mode') === 'direct')
                    ->required(fn (Get $get): bool => $get('affiliation_mode') === 'direct')
                    ->dehydrated(fn (Get $get): bool => $get('affiliation_mode') === 'direct'),
            ];

    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::getFormSchema());
    }
}
