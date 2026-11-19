<?php

namespace App\Services;

use App\Models\TournamentModality;
use Illuminate\Validation\ValidationException;

/** Una inscripción ocupa un cupo, tanto en individual como en equipos. No publica turnos completos. */
class TournamentSlotAvailability
{
    public function options(?TournamentModality $modality): array
    {
        $slots = $modality?->slots()->where('is_active', true)->orderBy('starts_at')
            ->withCount(['registrations as occupied_places' => fn ($query) => $query->whereNotIn('status', ['denegado', 'rechazado'])])->get() ?? collect();

        return [
            'requires_slot' => $modality?->slots()->exists() ?? false,
            'slots' => $slots->filter(fn ($slot) => $slot->max_players === null || $slot->occupied_places < $slot->max_players)
                ->map(fn ($slot) => [
                    'id' => $slot->id, 'name' => $slot->name, 'starts_at' => $slot->starts_at?->format('d/m/Y H:i'),
                    'capacity' => $slot->max_players,
                    'remaining' => $slot->max_players === null ? null : max(0, $slot->max_players - $slot->occupied_places),
                ])->values()->all(),
        ];
    }

    /** Se ejecuta dentro del bloqueo del torneo, inmediatamente antes del alta. */
    public function assertAvailable(TournamentModality $modality, ?int $slotId): void
    {
        if (! $slotId) {
            if ($modality->slots()->exists()) {
                throw ValidationException::withMessages(['tournament_slot_id' => 'Elegí un horario disponible. No se puede inscribir sin horario cuando el torneo tiene turnos configurados.']);
            }
            return;
        }
        $slot = $modality->slots()->where('is_active', true)->lockForUpdate()->find($slotId);
        if (! $slot) {
            throw ValidationException::withMessages(['tournament_slot_id' => 'Elegí un horario habilitado para esta modalidad.']);
        }
        if ($slot->max_players !== null && $slot->occupiedPlaces() >= $slot->max_players) {
            throw ValidationException::withMessages(['tournament_slot_id' => 'El horario acaba de completar su cupo. Elegí otro horario disponible.']);
        }
    }
}
