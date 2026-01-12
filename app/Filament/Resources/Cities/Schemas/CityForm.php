<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::schema());
    }

    public static function schema(): array
    {
        return [
            TextInput::make('name')
                ->label('Ciudad')
                ->required(),

            Select::make('state_id')
                ->relationship('state', 'name')
                ->label('Provincia')
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        $stateModel = \App\Models\State::find($state);
                        $set('country_name', $stateModel?->country?->name);
                        $set('country_id', $stateModel?->country?->id);
                    }
                }),

            Hidden::make('country_id')
                ->required(),

            TextInput::make('postal_code')
                ->label('Código Postal')
                ->numeric()
                ->nullable(),

            TextInput::make('country_name')
                ->label('País')
                ->disabled(),

            TextInput::make('latitude')
                ->label('Latitud')
                ->numeric(),

            TextInput::make('longitude')
                ->label('Longitud')
                ->numeric(),

            Toggle::make('is_active')
                ->label('¿Está activa?')
                ->required(),
        ];
    }
}
