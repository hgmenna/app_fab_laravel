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
                            ->label('Codigo')
                            ->columnSpan(1)
                            ->required(),
                        TextInput::make('points')
                            ->label('Puntos')
                            ->columnSpan(1)
                            ->required()
                            ->numeric(),
                        TextInput::make('description')
                            ->label('Descripcion')
                            ->columnSpan(1)
                            ->required(),
                        TextInput::make('instance')
                            ->label('Instancia')
                            ->columnSpan(1)
                            ->required()
                            ->numeric(),
                ])
                ->columns(4);
    }
}