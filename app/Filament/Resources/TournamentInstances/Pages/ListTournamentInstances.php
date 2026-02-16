<?php

namespace App\Filament\Resources\TournamentInstances\Pages;

use App\Filament\Resources\TournamentInstances\TournamentInstanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTournamentInstances extends ListRecords
{
    protected static string $resource = TournamentInstanceResource::class;
    protected static ?string $title = 'Puntuaciones de Torneos';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva Puntuacion'),
        ];
    }
}
