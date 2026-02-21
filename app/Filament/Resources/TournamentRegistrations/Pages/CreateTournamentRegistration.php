<?php

namespace App\Filament\Resources\TournamentRegistrations\Pages;

use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AdminNotifier;

class CreateTournamentRegistration extends CreateRecord
{
    protected static string $resource = TournamentRegistrationResource::class;
    protected static ?string $title = 'Nueva Inscripcion';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Obtenemos el nombre del torneo dinámicamente para el contexto
        $tournamentName = $this->record->tournament?->name ?? 'el torneo';

        AdminNotifier::send(
            $this, 
            $this->record, 
            'inscribió a', 
            ['player.last_name', 'player.first_name'], // Datos del jugador (relación)
            "el torneo {$tournamentName}"              // Recurso relacionado personalizado
        );
    }

    


}
