<?php

use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasEntry;
use App\Models\FiveQuillasMatch;
use App\Models\FiveQuillasPhase;
use App\Models\Player;
use App\Models\Tournament;
use App\Services\FiveQuillasCompetitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds a 64 player national bracket and stops at 16 qualifiers', function () {
    $competition = FiveQuillasCompetition::create([
        'tournament_id' => Tournament::factory()->create()->id,
        'public_slug' => 'national-bracket-test',
    ]);
    $previous = FiveQuillasPhase::create([
        'competition_id' => $competition->id,
        'code' => 'national-play-in',
        'name' => 'Emparejamientos',
        'phase_type' => 'play_in',
        'sort_order' => 1,
        'sets_to_play' => 3,
        'points_per_set' => 60,
        'seeding_method' => 'national_ranking',
        'status' => 'completed',
        'settings' => ['qualifiers' => 32],
    ]);
    $phase = FiveQuillasPhase::create([
        'competition_id' => $competition->id,
        'code' => 'national',
        'name' => 'Primera Nacional',
        'phase_type' => 'bracket',
        'sort_order' => 2,
        'sets_to_play' => 5,
        'points_per_set' => 60,
        'seeding_method' => 'national_ranking',
        'settings' => [
            'previous_phase_id' => $previous->id,
            'qualifiers' => 16,
            'direct_entry_seed_count' => 32,
            'bracket_size' => 128,
            'stop_after_rounds' => 1,
        ],
    ]);

    $qualifiedIds = collect(range(33, 64))->map(function (int $ranking) use ($competition, $previous): int {
        return FiveQuillasEntry::create([
            'competition_id' => $competition->id,
            'player_id' => Player::factory()->create()->id,
            'entry_phase_id' => $previous->id,
            'national_ranking_snapshot' => $ranking,
            'status' => 'qualified',
        ])->id;
    });
    $previous->update(['settings' => ['qualifiers' => 32, 'qualified_entry_ids' => $qualifiedIds->all()]]);

    collect(range(1, 32))->each(function (int $ranking) use ($competition, $phase): void {
        FiveQuillasEntry::create([
            'competition_id' => $competition->id,
            'player_id' => Player::factory()->create()->id,
            'entry_phase_id' => $phase->id,
            'national_ranking_snapshot' => $ranking,
            'status' => 'eligible',
        ]);
    });

    $service = app(FiveQuillasCompetitionService::class);
    expect($service->bracketParticipantRows($phase))->toHaveCount(64);
    expect($service->preparePhase($phase->fresh()))->toBe(48);

    $phase->refresh();
    expect(data_get($phase->settings, 'bracket_size'))->toBe(64)
        ->and($phase->matches()->where('round_number', 1)->count())->toBe(32)
        ->and($phase->matches()->where('round_number', 2)->count())->toBe(16)
        ->and($phase->matches()->max('round_number'))->toBe(2)
        ->and($competition->entries()->where('entry_phase_id', $phase->id)->max('seed'))->toBe(32)
        ->and($competition->entries()->where('entry_phase_id', $previous->id)->min('seed'))->toBe(33)
        ->and($competition->entries()->where('entry_phase_id', $previous->id)->max('seed'))->toBe(64);
});

it('moves direct overflow to reclassification without colliding seeds', function () {
    $competition = FiveQuillasCompetition::create([
        'tournament_id' => Tournament::factory()->create()->id,
        'public_slug' => 'national-overflow-test',
    ]);
    $previous = FiveQuillasPhase::create([
        'competition_id' => $competition->id, 'code' => 'play-in', 'name' => 'Emparejamientos',
        'phase_type' => 'play_in', 'sort_order' => 1, 'sets_to_play' => 3,
        'points_per_set' => 60, 'seeding_method' => 'national_ranking', 'status' => 'completed',
        'settings' => ['qualifiers' => 32],
    ]);
    $phase = FiveQuillasPhase::create([
        'competition_id' => $competition->id, 'code' => 'national', 'name' => 'Primera Nacional',
        'phase_type' => 'bracket', 'sort_order' => 2, 'sets_to_play' => 5,
        'points_per_set' => 60, 'seeding_method' => 'national_ranking',
        'settings' => ['previous_phase_id' => $previous->id, 'qualifiers' => 16,
            'direct_entry_seed_count' => 32, 'bracket_size' => 128],
    ]);

    $qualifiedIds = collect(range(34, 61))->map(fn (int $ranking): int => FiveQuillasEntry::create([
        'competition_id' => $competition->id, 'player_id' => Player::factory()->create()->id,
        'entry_phase_id' => $previous->id, 'national_ranking_snapshot' => $ranking,
        'status' => 'qualified',
    ])->id);
    $previous->update(['settings' => ['qualifiers' => 32, 'qualified_entry_ids' => $qualifiedIds->all()]]);
    $directIds = collect(range(1, 33))->map(fn (int $ranking): int => FiveQuillasEntry::create([
        'competition_id' => $competition->id, 'player_id' => Player::factory()->create()->id,
        'entry_phase_id' => $phase->id, 'national_ranking_snapshot' => $ranking,
        'status' => 'eligible',
    ])->id);
    $phase->update(['settings' => array_merge($phase->settings, [
        'participant_seed_overrides' => [
            $directIds->first() => 65,
            $qualifiedIds->first() => 33,
        ],
    ])]);

    $service = app(FiveQuillasCompetitionService::class);
    $rows = collect($service->bracketParticipantRows($phase));
    expect($rows)->toHaveCount(61)
        ->and($rows->where('origin_type', 'direct'))->toHaveCount(32)
        ->and($rows->where('origin_type', 'qualified'))->toHaveCount(29)
        ->and($rows->pluck('seed_position')->unique())->toHaveCount(61)
        ->and($rows->max('seed_position'))->toBe(61)
        ->and(data_get($phase->fresh()->settings, 'bracket_size'))->toBe(64);

    $service->saveBracketParticipantOrder($phase, $rows->all());
    $service->preparePhase($phase->fresh());
    $phase->refresh();
    $assigned = $phase->matches()->where('round_number', 1)->get()
        ->flatMap(fn ($match) => [$match->player_one_entry_id, $match->player_two_entry_id])->filter()->unique();
    expect(data_get($phase->settings, 'bracket_size'))->toBe(64)
        ->and($assigned)->toHaveCount(61);
});

it('uses every completed slot crossing winner in the configured 64 player bracket', function () {
    $competition = FiveQuillasCompetition::create([
        'tournament_id' => Tournament::factory()->create()->id,
        'public_slug' => 'slot-winners-national-test',
    ]);
    $previous = FiveQuillasPhase::create([
        'competition_id' => $competition->id, 'code' => 'slot-crossings',
        'name' => 'Eliminación por horario', 'phase_type' => 'slot_elimination',
        'sort_order' => 2, 'sets_to_play' => 3, 'points_per_set' => 60,
        'seeding_method' => 'manual', 'status' => 'completed',
        'settings' => ['qualifiers' => 32],
    ]);
    $phase = FiveQuillasPhase::create([
        'competition_id' => $competition->id, 'code' => 'national',
        'name' => 'Primera Nacional', 'phase_type' => 'bracket', 'sort_order' => 3,
        'sets_to_play' => 5, 'points_per_set' => 60,
        'seeding_method' => 'national_ranking',
        'settings' => ['previous_phase_id' => $previous->id, 'qualifiers' => 16,
            'direct_entry_seed_count' => 32, 'bracket_size' => 128],
    ]);

    collect(range(33, 64))->each(function (int $ranking, int $index) use ($competition, $previous): void {
        $winner = FiveQuillasEntry::create([
            'competition_id' => $competition->id, 'player_id' => Player::factory()->create()->id,
            'entry_phase_id' => $previous->id, 'national_ranking_snapshot' => $ranking,
            'status' => 'qualified',
        ]);
        FiveQuillasMatch::create([
            'phase_id' => $previous->id,
            // Los cruces pueden haberse cargado en tandas con números de ronda
            // históricos diferentes; todos sus ganadores deben reunirse.
            'round_number' => $index < 16 ? 1 : 2,
            'bracket_order' => $index + 1,
            'winner_entry_id' => $winner->id,
            'player_one_entry_id' => $winner->id,
            'status' => 'completed',
        ]);
    });
    collect(range(1, 32))->each(fn (int $ranking) => FiveQuillasEntry::create([
        'competition_id' => $competition->id, 'player_id' => Player::factory()->create()->id,
        'entry_phase_id' => $phase->id, 'national_ranking_snapshot' => $ranking,
        'status' => 'eligible',
    ]));

    $service = app(FiveQuillasCompetitionService::class);
    $rows = collect($service->bracketParticipantRows($phase));
    expect($rows)->toHaveCount(64)
        ->and($rows->where('origin_type', 'direct'))->toHaveCount(32)
        ->and($rows->where('origin_type', 'qualified'))->toHaveCount(32)
        ->and(data_get($phase->fresh()->settings, 'bracket_size'))->toBe(64);

    expect($service->preparePhase($phase->fresh()))->toBe(48)
        ->and($phase->matches()->where('round_number', 1)->count())->toBe(32)
        ->and($phase->matches()->where('round_number', 2)->count())->toBe(16)
        ->and($phase->matches()->where('round_number', 1)->whereNull('player_one_entry_id')->count())->toBe(0)
        ->and($phase->matches()->where('round_number', 1)->whereNull('player_two_entry_id')->count())->toBe(0);
});
