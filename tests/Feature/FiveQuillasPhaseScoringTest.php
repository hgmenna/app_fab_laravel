<?php

use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasEntry;
use App\Models\FiveQuillasGroup;
use App\Models\FiveQuillasGroupEntry;
use App\Models\FiveQuillasMatch;
use App\Models\FiveQuillasPhase;
use App\Models\Tournament;
use App\Models\TournamentType;
use App\Services\FiveQuillasCompetitionService;
use Illuminate\Database\Eloquent\Collection;

function phaseScoringFixture(int $qualifiers = 24, int $size = 32): array
{
    $rules = collect([
        [1, 1, 50], [2, 2, 35], [3, 4, 20], [5, 8, 10],
        [9, 16, 6], [17, 32, 3], [33, 64, 2], [65, 128, 1],
    ])->map(fn ($rule) => ['code' => 'P'.$rule[0], 'description' => $rule[0].' / '.$rule[1], 'points' => $rule[2]])->all();
    $tournament = new Tournament;
    $tournament->setRelation('type', new TournamentType(['scoring_rules' => $rules]));
    $competition = new FiveQuillasCompetition;
    $entries = new Collection;
    for ($id = 1; $id <= 48; $id++) {
        $entry = new FiveQuillasEntry(['final_position' => $id]);
        $entry->id = $id;
        $entries->push($entry);
    }
    $zones = new FiveQuillasPhase(['phase_type' => 'zones', 'sort_order' => 1, 'is_enabled' => true]);
    $group = new FiveQuillasGroup;
    $group->setRelation('groupEntries', new Collection($entries->map(fn ($entry) => new FiveQuillasGroupEntry([
        'entry_id' => $entry->id, 'final_place' => $entry->id <= $qualifiers ? 1 : 3,
    ]))->all()));
    $zones->setRelation('groups', new Collection([$group]));
    $zones->setRelation('matches', new Collection);
    $bracket = new FiveQuillasPhase(['phase_type' => 'bracket', 'sort_order' => 2, 'is_enabled' => true,
        'settings' => ['bracket_size' => $size]]);
    $matches = new Collection;
    // Build backwards from the final. Eight empty slots in the first round of
    // a 32-place bracket leave exactly eight real first-round eliminations.
    for ($remaining = 2, $round = (int) log($size, 2); $remaining <= $size; $remaining *= 2, $round--) {
        for ($winner = 1; $winner <= $remaining / 2; $winner++) {
            $loser = $winner + $remaining / 2;
            $match = new FiveQuillasMatch(['round_number' => $round, 'status' => 'completed',
                'player_one_entry_id' => $winner <= $qualifiers ? $winner : null,
                'player_two_entry_id' => $loser <= $qualifiers ? $loser : null,
                'winner_entry_id' => $winner <= $qualifiers ? $winner : null,
                'label' => $remaining === 2 ? 'Final' : 'Ronda '.$remaining]);
            $match->setRelation('phase', $bracket);
            $matches->push($match);
        }
    }
    $bracket->setRelation('matches', $matches);
    $bracket->setRelation('groups', new Collection);
    $competition->setRelation('phases', new Collection([$zones, $bracket]));
    $competition->setRelation('entries', $entries);

    return [$tournament, $competition, $entries, $bracket];
}

it('awards round-of-32 points to only eight eliminations when 24 players qualify', function () {
    [$tournament, $competition, $entries] = phaseScoringFixture();
    $service = app(FiveQuillasCompetitionService::class);
    $points = $entries->map(fn ($entry) => $service->proposedPointsForEntry($tournament, $competition, $entry));

    expect($points->filter(fn ($value) => $value === 3.0)->count())->toBe(8)
        ->and($points->slice(24)->unique()->values()->all())->toBe([2.0])
        ->and($points->take(16)->all())->toBe([50.0, 35.0, 20.0, 20.0, 10.0, 10.0, 10.0, 10.0, 6.0, 6.0, 6.0, 6.0, 6.0, 6.0, 6.0, 6.0])
        ->and($entries[24]->final_position)->toBe(25)
        ->and($service->finalInstanceForEntry($competition, $entries[24]))->toBe('Zonas · 3.º puesto');
});

it('preserves scoring when the final bracket has no empty places', function () {
    [$tournament, $competition, $entries] = phaseScoringFixture(32);
    $service = app(FiveQuillasCompetitionService::class);
    expect($service->proposedPointsForEntry($tournament, $competition, $entries[31]))->toBe(3.0)
        ->and($service->proposedPointsForEntry($tournament, $competition, $entries[32]))->toBe(2.0);
});

it('uses the configured ZONAS point for every zone elimination, including places 25 through 32', function () {
    [$tournament, $competition, $entries] = phaseScoringFixture();
    // This is the table configured for ARGENTINO 2da CAT: no numeric
    // ranges after 32, and one point for anyone eliminated in zones.
    $tournament->type->scoring_rules = array_merge(
        array_slice($tournament->type->scoring_rules, 0, 6),
        [['code' => 'ZONAS', 'description' => 'ZONAS', 'points' => 1]],
    );
    $service = app(FiveQuillasCompetitionService::class);
    $points = $entries->map(fn ($entry) => $service->proposedPointsForEntry($tournament, $competition, $entry));

    expect($points->filter(fn ($value) => $value === 3.0)->count())->toBe(8)
        ->and($points->filter(fn ($value) => $value === 1.0)->count())->toBe(24)
        ->and($points->slice(24)->unique()->all())->toBe([24 => 1.0])
        ->and($points->contains(null))->toBeFalse();
});

it('uses the real final placement in the terminal bracket even when it contains byes', function () {
    [$tournament, $competition, $entries, $bracket] = phaseScoringFixture();
    $bracket->settings = ['bracket_size' => 64];
    // The same 24 players played one round earlier in a 64-place draw.
    $bracket->matches->each(fn ($match) => $match->round_number++);
    // Move only first-round losses back to the 64-place opening round.
    $bracket->matches->where('round_number', 2)->each(fn ($match) => $match->round_number = 1);
    $service = app(FiveQuillasCompetitionService::class);
    expect($service->proposedPointsForEntry($tournament, $competition, $entries[16]))->toBe(3.0)
        ->and($service->proposedPointsForEntry($tournament, $competition, $entries[24]))->toBe(1.0);
});

it('preserves explicit national phase rules over numeric ranges', function () {
    [$tournament, $competition, $entries, $bracket] = phaseScoringFixture();
    $bracket->name = 'Primera Nacional';
    $masters = new FiveQuillasPhase(['phase_type' => 'bracket', 'sort_order' => 3, 'is_enabled' => true]);
    $masters->setRelation('matches', new Collection);
    $masters->setRelation('groups', new Collection);
    $competition->phases->push($masters);
    $tournament->type->scoring_rules = array_merge($tournament->type->scoring_rules, [
        ['code' => 'NAC1', 'description' => 'F1 NAC', 'points' => 10],
        ['code' => 'NAC2', 'description' => 'F2 NAC', 'points' => 12],
    ]);
    $service = app(FiveQuillasCompetitionService::class);
    expect($service->proposedPointsForEntry($tournament, $competition, $entries[16]))->toBe(10.0)
        ->and($service->proposedPointsForEntry($tournament, $competition, $entries[8]))->toBe(12.0);
});

it('labels the completed tournament champion and runner-up without changing their points', function () {
    [$tournament, $competition, $entries] = phaseScoringFixture();
    $competition->status = 'completed';
    $service = app(FiveQuillasCompetitionService::class);

    expect($service->finalInstanceForEntry($competition, $entries[0]))->toBe('Campeón')
        ->and($service->finalInstanceForEntry($competition, $entries[1]))->toBe('Subcampeón')
        ->and($service->proposedPointsForEntry($tournament, $competition, $entries[0]))->toBe(50.0)
        ->and($service->proposedPointsForEntry($tournament, $competition, $entries[1]))->toBe(35.0);

    $competition->status = 'in_progress';
    expect($service->finalInstanceForEntry($competition, $entries[0]))->toBe('Final');
});

it('uses every individually configured final position and its exact scoring description', function () {
    [$tournament, $competition, $entries] = phaseScoringFixture(32);
    $tournament->type->scoring_rules = collect(range(1,32))->map(fn ($place) => [
        'code' => 'POSITION_'.$place, 'description' => 'Puesto '.$place, 'points' => 1000-$place,
    ])->all();
    $competition->setRelation('tournament', $tournament);
    $service = app(FiveQuillasCompetitionService::class);
    foreach ($entries->take(32) as $i => $entry) {
        expect($service->proposedPointsForEntry($tournament, $competition, $entry))->toBe((float) (999-$i))
            ->and($service->finalInstanceForEntry($competition, $entry))->toBe('Puesto '.($i+1));
    }
});

it('scores slot crossing losers by their original first or second zone place', function () {
    [$tournament, $competition, $entries, $bracket] = phaseScoringFixture();
    $bracket->sort_order = 3;
    $crossings = new FiveQuillasPhase(['phase_type' => 'slot_elimination', 'sort_order' => 2, 'is_enabled' => true]);
    $matches = new Collection;
    foreach ([24 => 1, 25 => 2] as $index => $zonePlace) {
        $competition->phases->first()->groups->first()->groupEntries->firstWhere('entry_id', $entries[$index]->id)->final_place = $zonePlace;
        $match = new FiveQuillasMatch(['round_number' => 1, 'status' => 'completed', 'player_one_entry_id' => $entries[$index]->id,
            'player_two_entry_id' => $entries[0]->id, 'winner_entry_id' => $entries[0]->id, 'label' => 'Cruce por horario']);
        $match->setRelation('phase', $crossings);
        $matches->push($match);
    }
    $crossings->setRelation('matches', $matches);
    $crossings->setRelation('groups', new Collection);
    $competition->phases->push($crossings);
    $tournament->type->scoring_rules = array_merge($tournament->type->scoring_rules, [
        ['code' => 'ZONE_FIRST', 'description' => '1° ZONA', 'points' => 6],
        ['code' => 'ZONE_SECOND', 'description' => '2° ZONA', 'points' => 5],
    ]);
    $competition->setRelation('tournament', $tournament);
    $service = app(FiveQuillasCompetitionService::class);
    expect($service->proposedPointsForEntry($tournament, $competition, $entries[24]))->toBe(6.0)
        ->and($service->proposedPointsForEntry($tournament, $competition, $entries[25]))->toBe(5.0)
        ->and($service->finalInstanceForEntry($competition, $entries[24]))->toBe('1° ZONA')
        ->and($service->finalInstanceForEntry($competition, $entries[25]))->toBe('2° ZONA');
});
