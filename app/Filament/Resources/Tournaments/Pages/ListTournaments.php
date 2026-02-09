<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Table;
use App\Filament\Resources\Tournaments\Pages\ViewRegistrations;
use Filament\Actions\Action;

class ListTournaments extends ListRecords
{
    protected static string $resource = TournamentResource::class;
    protected static ?string $title = 'Torneos';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo Torneo'),
        ];
    }

   /*
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                // tus columnas
            ]);
    }
            */


}
