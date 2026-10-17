<?php

namespace App\Filament\Resources\Memberships\Tables;

use App\Filament\Actions\GlobalEditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MembershipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('year')->label('Año')->sortable(),
                TextColumn::make('discipline.name')->label('Disciplina')->searchable()->sortable(),
                TextColumn::make('amount')->label('Importe')->money('ARS')->sortable(),
                TextColumn::make('due_date')->label('Fecha de cierre')->date('d/m/Y')->sortable(),
                TextColumn::make('player_memberships_count')->label('Renovadas')->counts('playerMemberships')->alignCenter(),
                IconColumn::make('active')->label('Activa')->boolean(),
            ])
            ->recordActions([
                GlobalEditAction::make()->iconButton()->tooltip('Editar configuración anual'),
            ]);
    }
}
