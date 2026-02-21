<?php

namespace App\Filament\Resources\TournamentRegistrations\Pages;

use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use Filament\Resources\Pages\ListRecords;

class ListTournamentRegistrations extends ListRecords
{
    protected static string $resource = TournamentRegistrationResource::class;
    protected static ?string $navigationLabel = 'Inscripciones';
}

