<?php

namespace App\Filament\Resources\TournamentRegistration\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;

class TournamentPlayersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('player.name')
                    ->label('Nombre del Jugador')
                    ->searchable(),
                Tables\Columns\TextColumn::make('player.club')
                    ->label('Club'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría'),
            ])
            ->recordActions([
                // Acciones requeridas para editar y eliminar registros individuales [2, 6]
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}