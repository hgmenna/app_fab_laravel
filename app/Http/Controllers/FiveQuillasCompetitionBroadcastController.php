<?php

namespace App\Http\Controllers;

use App\Helpers\CompetitionViewVersion;
use App\Models\FiveQuillasCompetition;
use Illuminate\Http\Response;

/**
 * Renderiza la fuente animada de resultados que OBS incorpora como navegador.
 */
class FiveQuillasCompetitionBroadcastController extends Controller
{
    public function __invoke(string $slug): Response
    {
        $competition = FiveQuillasCompetition::query()
            ->where('public_slug', $slug)
            ->with(['tournament.type.publicationFederation', 'tournament.venue.city.state.federation',
                'tournament.externalVenueState.federation', 'tournament.tournamentModalities.modality'])
            ->firstOrFail();

        $settings = (array) data_get($competition->settings, 'broadcast', []);
        if (! $competition->is_public || ! (bool) ($settings['enabled'] ?? false)) {
            return response()->view('public.five-quillas-competition-unavailable', ['competition' => $competition])
                ->header('Cache-Control', 'no-store');
        }

        return response()->view('public.five-quillas-competition-broadcast', [
            'competition' => $competition,
            'viewVersion' => CompetitionViewVersion::current(),
            'jsonUrl' => route('five-quillas-competition.public', ['slug' => $slug]),
            'broadcastConfig' => [
                'enabled' => true,
                'allPhases' => (bool) ($settings['all_phases'] ?? true),
                'phaseIds' => collect($settings['phase_ids'] ?? [])->map(fn ($id): int => (int) $id)->values()->all(),
                'allZoneSlots' => (bool) ($settings['all_zone_slots'] ?? empty($settings['zone_slot_keys'] ?? [])),
                'zoneSlotKeys' => collect($settings['zone_slot_keys'] ?? [])->map(fn ($key): string => (string) $key)->values()->all(),
                'finalSections' => $settings['final_sections'] ?? 'both',
                'rotationSeconds' => max(4, min(60, (int) ($settings['rotation_seconds'] ?? 8))),
            ],
        ])->header('Cache-Control', 'no-store');
    }
}
