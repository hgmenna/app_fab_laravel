<?php

namespace App\Filament\Resources\TournamentInstances\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TournamentInstanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                        TextInput::make('code')
                            ->columnSpan(1)
                            ->required(),
                        TextInput::make('points')
                            ->columnSpan(1)
                            ->required()
                            ->numeric(),
                        TextInput::make('description')
                            ->columnSpan(1)
                            ->required(),
                        TextInput::make('instance')
                            ->columnSpan(1)
                            ->required()
                            ->numeric(),
                ])
                ->columns(4);
    }
}