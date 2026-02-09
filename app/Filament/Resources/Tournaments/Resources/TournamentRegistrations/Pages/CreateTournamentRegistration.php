<?php

namespace App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Pages;

use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\TournamentRegistrationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTournamentRegistration extends CreateRecord
{
    protected static string $resource = TournamentRegistrationResource::class;
    protected static ?string $title = 'Nueva Inscripcion';
}
