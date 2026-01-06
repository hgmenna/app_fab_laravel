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
                    ->required(),
                TextInput::make('points')
                    ->required()
                    ->numeric(),
                TextInput::make('description')
                    ->required(),
                TextInput::make('instance')
                    ->required()
                    ->numeric(),
            ]);
    }
}
