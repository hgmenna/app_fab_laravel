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
                    ->label('Nombre')
                    ->required(),
                TextInput::make('code')
                    ->label('Código')
                    ->default(null),
                Toggle::make('is_official')
                    ->label('Es oficial')
                    ->required(),
                Toggle::make('affects_ranking')
                    ->label('Afecta al ranking')
                    ->required(),
                Toggle::make('assigns_points')
                    ->label('Asigna puntos')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Está activo')
                    ->required(),
                TextInput::make('score_percentage')
                    ->label('Porcentaje de puntuación')
                    ->required()
                    ->numeric()
                    ->default(100.0),
            ]);
    }
}
