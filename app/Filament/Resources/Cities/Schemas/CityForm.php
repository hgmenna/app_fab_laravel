<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use App\Models\Country;

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
            Select::make('country_id')
                        ->required()
                        ->label('País')
                        ->searchable()
                        ->live()
                        ->options(Country::pluck('name', 'id')->toArray())
                        ->default(function () {
                            return \App\Models\Country::where('name', 'Argentina')->value('id');
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('state_id', null);
                        }),
            Select::make('state_id')
                ->relationship('state', 'name')
                ->label('Provincia')
                ->options(function (callable $get) {
                        $countryId = $get('country_id');

                        if (!$countryId) return [];

                        return \App\Models\State::where('country_id', $countryId)
                            ->pluck('name', 'id')
                            ->toarray();
                    })
                ->reactive(),

            TextInput::make('postal_code')
                ->label('Código Postal')
                ->numeric()
                ->nullable(),

            TextInput::make('latitude')
                ->label('Latitud')
                ->numeric(),

            TextInput::make('longitude')
                ->label('Longitud')
                ->numeric(),

            Toggle::make('is_active')
                ->label('¿Está activa?')
                ->default(true)
                ->required(),
        ];
    }
}
