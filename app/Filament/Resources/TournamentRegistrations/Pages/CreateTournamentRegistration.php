<?php

namespace App\Filament\Resources\TournamentRegistrations\Pages;

use App\Filament\Resources\TournamentRegistrations\TournamentRegistrationResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\TournamentRegistration;
use Illuminate\Support\Facades\Request;
use Filament\Pages\Concerns\InteractsWithHeaderActions;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateTournamentRegistration extends CreateRecord
{
   protected static string $resource = TournamentRegistrationResource::class;

    /**
     * Antes de crear, validamos las reglas institucionales:
     * - Un jugador solo puede inscribirse una vez por torneo
     * - No puede inscribirse dos veces al mismo horario
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Un jugador solo puede tener UNA inscripción por torneo
        $existsInTournament = TournamentRegistration::query()
            ->where('player_id', $data['player_id'])
            ->where('tournament_id', $data['tournament_id'])
            ->exists();

        if ($existsInTournament) {
            Notification::make()
                ->title('El jugador ya está inscripto en este torneo.')
                ->danger()
                ->send();

            $this->halt();
        }

        // Refuerzo: no permitir duplicar exactamente el mismo horario
        $existsInSlot = TournamentRegistration::query()
            ->where('player_id', $data['player_id'])
            ->where('tournament_slot_id', $data['tournament_slot_id'])
            ->exists();

        if ($existsInSlot) {
            Notification::make()
                ->title('El jugador ya está inscripto en este horario.')
                ->danger()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($tournamentId = Request::query('tournament_id')) {
            $data['tournament_id'] = $tournamentId;
        }

        return $data;
    }

}
