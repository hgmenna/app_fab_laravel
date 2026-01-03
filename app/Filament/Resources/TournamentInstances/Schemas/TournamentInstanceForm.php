<?php

namespace App\Filament\Resources\TournamentInstances\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TournamentInstanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Código')
                    ->numeric()
                    ->required(),
                TextInput::make('points')
                    ->label('Puntos')
                    ->required()
                    ->numeric(),
                TextInput::make('description')
                    ->label('Descripción')
                    ->required(),
                TextInput::make('instance')
                    ->label('Instancia')
                    ->required(),
            ]);
    }
}
