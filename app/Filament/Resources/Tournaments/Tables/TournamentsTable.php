<?php

namespace App\Filament\Resources\Tournaments\Tables;

use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use App\Models\Tournament;

class TournamentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Torneo')
                    ->searchable()
                    ->sortable()
                    ->alignCenter(),
                    
                TextColumn::make('type.code')
                    ->label('Tipo')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('venue.name')
                    ->label('Club organizador')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date()
                    ->sortable()
                    ->alignCenter(),    
    
                TextColumn::make('registrations_count')
                    ->label('Inscriptos')
                    ->counts('registrations')
                    ->alignCenter(),

                IconColumn::make('is_payment_enabled')
                    ->label('Requiere Pago')
                    ->alignCenter(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver')
                    ->iconButton(),
                EditAction::make()
                    ->label('Editar')
                    ->iconButton(),
                DeleteAction::make()
                    ->label('Eliminar')
                    ->iconButton(),
                Action::make('manageRegistrations')
                    ->label('Inscripciones')
                    ->icon('heroicon-o-users')
                    ->color('info')
                    // Genera la URL usando el nombre de la página que registraste en getPages()
                    ->url(fn (Tournament $record): string => TournamentResource::getUrl('registrations', ['record' => $record])),
            ]);
    }

}
