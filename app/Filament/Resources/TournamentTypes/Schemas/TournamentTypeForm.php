<?php

namespace App\Filament\Resources\TournamentTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TournamentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('code')
                    ->default(null),
                Toggle::make('is_official')
                    ->required(),
                Toggle::make('affects_ranking')
                    ->required(),
                Toggle::make('assigns_points')
                    ->required(),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('score_percentage')
                    ->required()
                    ->numeric()
                    ->default(100.0),
            ]);
    }
}
