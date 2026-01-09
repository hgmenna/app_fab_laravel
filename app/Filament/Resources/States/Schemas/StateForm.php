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
                Select::make('country_id')
                    ->relationship('country', 'name')
                    ->reactive()
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Select::make('federation_id')
                    ->relationship('federation', 'name')
                    ->label('Federación')
                    ->reactive()
                    ->searchable()
                    ->createOptionForm(
                        \App\Filament\Resources\Federations\Schemas\FederationForm::schema()
                    )
                    ->createOptionAction(fn($action) => $action->modalHeading('Crear nueva Federación')),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('latitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('longitude')
                    ->numeric()
                    ->default(null),
            ]);
    }
}
