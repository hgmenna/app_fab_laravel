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
                TextInput::make('country_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('latitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('longitude')
                    ->numeric()
                    ->default(null),
                Toggle::make('is_active')
                    ->required(),
                Select::make('federation_id')
                    ->relationship('federation', 'name')
                    ->default(null)
                    ->nullable()
                    ->label('Federación')
                    ->searchable()
                    ->createOptionForm(
                        \App\Filament\Resources\Federations\Schemas\FederationForm::schema()
                    )
                    ->createOptionAction(fn($action) => $action->modalHeading('Crear nueva Federación')),
            ]);
    }
}
