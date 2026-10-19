<?php

namespace App\Http\Controllers;

use App\Models\FiveQuillasCompetition;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FiveQuillasCompetitionSectionPdfController extends Controller
{
    public function __invoke(string $slug, string $section): Response
    {
        $competition = FiveQuillasCompetition::query()
            ->where('public_slug', $slug)
            ->with([
                'tournament.type.publicationFederation',
                'tournament.venue.city.state.federation',
                'tournament.externalVenueState.federation',
                'tournament.tournamentModalities.modality',
                'phases' => fn ($query) => $query->where('is_public', true)->where('is_enabled', true),
                'phases.groups' => fn ($query) => $query->where('is_public', true),
                'phases.groups.slot',
                'phases.groups.groupEntries.entry.player.club',
                'phases.groups.matches.playerOne.player',
                'phases.groups.matches.playerTwo.player',
                'phases.matches' => fn ($query) => $query->where('is_public', true),
                'phases.matches.playerOne.player', 'phases.matches.playerTwo.player', 'phases.matches.winner.player',
                'entries.player.club', 'entries.player.category', 'entries.registration',
            ])->firstOrFail();

        abort_unless($competition->is_public || auth()->check(), 404);

        $phase = null;
        if ($section !== 'positions') {
            abort_unless(str_starts_with($section, 'phase-'), 404);
            $phase = $competition->phases->firstWhere('id', (int) substr($section, 6));
            abort_unless($phase, 404);
        }

        $tournament = $competition->tournament;
        $logoPath = $tournament->publicationLogoPath();
        $logoFile = $logoPath ? Storage::disk('public_path')->path($logoPath) : null;
        $finalRows = $competition->entries->whereNotNull('final_position')->sortBy('final_position')
            ->map(function ($entry) use ($competition): array {
                $performance = $this->entryPerformance($competition, (int) $entry->id);

                return [
                    'position' => $entry->final_position,
                    'player' => $entry->player?->full_name,
                    'club' => $entry->player?->club?->short_name ?: $entry->player?->club?->name,
                    'category' => $entry->player?->category?->code ?: $entry->player?->category?->name,
                    'instance' => $this->finalInstance($competition, $entry),
                    'sets_difference' => $performance['sets_difference'],
                    'average' => number_format($performance['average'], 3, ',', '.'),
                    'penalty' => $entry->registration?->penalty_points ?? 0,
                    'points' => $entry->registration?->points,
                ];
            });

        return Pdf::loadView('pdf.five-quillas-competition-section', [
            'competition' => $competition,
            'phase' => $phase,
            'section' => $section,
            'logoFile' => $logoFile && is_file($logoFile) ? $logoFile : null,
            'finalRows' => $finalRows,
            'tournamentInfo' => [
                'name' => $tournament->name,
                'venue' => $tournament->venueName(),
                'date' => $tournament->start_date
                    ? ($tournament->end_date && ! $tournament->end_date->isSameDay($tournament->start_date)
                        ? $tournament->start_date->format('d/m/Y').' al '.$tournament->end_date->format('d/m/Y')
                        : $tournament->start_date->format('d/m/Y')) : 'Sin fecha',
                'type' => $tournament->type?->name ?? 'Sin tipo',
                'official' => $tournament->type?->is_official ? 'Oficial' : 'No oficial',
                'modality' => $tournament->tournamentModalities->pluck('modality.name')->filter()->implode(', ') ?: 'Sin modalidad',
            ],
        ])->setPaper('a4', 'landscape')->stream(
            str($tournament->name.'-'.($phase?->name ?? 'posiciones-finales'))->slug().'.pdf',
            ['Attachment' => false]
        );
    }

    private function finalInstance(FiveQuillasCompetition $competition, $entry): string
    {
        $lastMatch = $competition->phases->where('phase_type', '!=', 'zones')->flatMap->matches
            ->filter(fn ($match): bool => in_array((int) $entry->id, [(int) $match->player_one_entry_id, (int) $match->player_two_entry_id], true))
            ->sortByDesc(fn ($match): string => sprintf('%05d-%05d', $match->phase?->sort_order ?? 0, $match->round_number))->first();
        if ($lastMatch) {
            return preg_replace('/\s+\d+$/', '', (string) $lastMatch->label) ?: $lastMatch->phase?->name;
        }

        $zoneRow = $competition->phases->flatMap->groups->flatMap->groupEntries->firstWhere('entry_id', $entry->id);

        return $zoneRow ? 'Zonas · '.$zoneRow->final_place.'.º puesto' : 'Sin instancia';
    }

    private function entryPerformance(FiveQuillasCompetition $competition, int $entryId): array
    {
        $setsFor = $setsAgainst = $pointsFor = $pointsAgainst = 0;
        foreach ($competition->phases->flatMap->matches->where('status', 'completed')->where('is_bye', false) as $match) {
            $isOne = (int) $match->player_one_entry_id === $entryId;
            $isTwo = (int) $match->player_two_entry_id === $entryId;
            if (! $isOne && ! $isTwo) { continue; }
            $setsFor += $isOne ? (int) $match->player_one_computed_sets : (int) $match->player_two_computed_sets;
            $setsAgainst += $isOne ? (int) $match->player_two_computed_sets : (int) $match->player_one_computed_sets;
            $pointsFor += $isOne ? (int) $match->player_one_points : (int) $match->player_two_points;
            $pointsAgainst += $isOne ? (int) $match->player_two_points : (int) $match->player_one_points;
        }

        return ['sets_difference' => $setsFor - $setsAgainst, 'average' => $pointsAgainst > 0 ? $pointsFor / $pointsAgainst : 0];
    }
}
