<?php

namespace App\Filament\Resources\States\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Select::make('country_id')
                    ->label('Pais')
                    ->relationship('country', 'name')
                    ->reactive()
                    ->required(),
                Select::make('federation_id')
                    ->relationship('federation', 'name')
                    ->label('Federacion')
                    ->preload()
                    ->searchable(),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->required(),
                TextInput::make('latitude')
                    ->label('Latitud')
                    ->numeric()
                    ->default(null),
                TextInput::make('longitude')
                    ->label('Longitud')
                    ->numeric()
                    ->default(null),
        ]);
    }
}
