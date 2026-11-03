<?php

use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasEntry;
use App\Models\FiveQuillasMatch;
use App\Models\FiveQuillasPhase;
use App\Services\FiveQuillasCompetitionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('categories', function (Blueprint $t) { $t->id(); $t->string('code'); $t->string('name')->nullable(); $t->unsignedBigInteger('discipline_id')->nullable(); });
    Schema::create('five_quillas_competitions', function (Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('tournament_id')->nullable(); $t->string('status')->default('in_progress'); $t->json('settings')->nullable(); $t->timestamps();
    });
    Schema::create('five_quillas_phases', function (Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('competition_id'); $t->string('phase_type')->default('bracket');
        $t->string('code')->nullable(); $t->string('name')->nullable();
        $t->integer('points_per_set')->default(60); $t->boolean('split_by_slot')->default(false); $t->boolean('is_public')->default(false);
        $t->integer('sort_order')->default(1); $t->boolean('is_enabled')->default(true);
        $t->integer('sets_to_play')->default(3); $t->string('status')->default('ready');
        $t->string('seeding_method')->default('number_draw'); $t->json('settings')->nullable(); $t->timestamps();
    });
    Schema::create('five_quillas_entries', function (Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('competition_id'); $t->unsignedBigInteger('entry_phase_id')->nullable();
        $t->unsignedBigInteger('player_id')->nullable(); $t->string('status')->default('assigned'); $t->timestamps();
        $t->unsignedBigInteger('source_slot_id')->nullable();
        $t->unsignedBigInteger('tournament_registration_id')->nullable(); $t->integer('final_position')->nullable();
        $t->integer('seed')->nullable(); $t->integer('national_ranking_snapshot')->nullable();
    });
    Schema::create('five_quillas_groups', function (Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('phase_id'); $t->integer('sort_order')->default(1);
        $t->string('status')->default('completed'); $t->timestamps();
    });
    Schema::create('five_quillas_group_entries', function (Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('group_id'); $t->unsignedBigInteger('entry_id');
        $t->integer('position'); $t->integer('final_place')->nullable(); $t->timestamps();
        $t->integer('sets_difference')->default(0); $t->decimal('points_average', 8, 3)->default(0);
        $t->integer('points_for')->default(0); $t->integer('match_wins')->default(0);
    });
    Schema::create('five_quillas_matches', function (Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('phase_id'); $t->integer('round_number')->default(1);
        $t->string('label')->nullable(); $t->unsignedBigInteger('group_id')->nullable(); $t->boolean('is_public')->default(false);
        $t->integer('bracket_order')->default(1);
        foreach (['player_one_entry_id','player_two_entry_id','winner_entry_id','next_match_id','loser_next_match_id','next_match_position'] as $key) $t->unsignedBigInteger($key)->nullable();
        foreach (['player_one_actual_sets','player_two_actual_sets','player_one_computed_sets','player_two_computed_sets'] as $key) $t->integer($key)->default(0);
        $t->json('set_scores')->nullable(); $t->boolean('is_bye')->default(false);
        $t->dateTime('scheduled_at')->nullable(); $t->unsignedInteger('table_number')->nullable();
        $t->unsignedInteger('estimated_minutes')->nullable();
        $t->integer('player_one_points')->default(0); $t->integer('player_two_points')->default(0);
        $t->string('status')->default('pending'); $t->timestamps();
    });
    Schema::create('tournament_registrations', function (Blueprint $t) { $t->id(); $t->decimal('points')->nullable(); });
    $this->competition = FiveQuillasCompetition::create([]);
    $this->phase = FiveQuillasPhase::create(['competition_id' => $this->competition->id]);
    $this->entries = collect(range(1,3))->map(fn () => FiveQuillasEntry::create(['competition_id' => $this->competition->id]));
    $this->final = FiveQuillasMatch::create(['phase_id' => $this->phase->id, 'round_number' => 2]);
    $this->bye = FiveQuillasMatch::create(['phase_id' => $this->phase->id,
        'player_one_entry_id' => $this->entries[0]->id, 'next_match_id' => $this->final->id, 'next_match_position' => 1]);
    $this->played = FiveQuillasMatch::create(['phase_id' => $this->phase->id, 'bracket_order' => 2,
        'player_one_entry_id' => $this->entries[1]->id, 'player_two_entry_id' => $this->entries[2]->id,
        'next_match_id' => $this->final->id, 'next_match_position' => 2]);
});

afterEach(function () {
    foreach (['tournament_registrations','five_quillas_group_entries','five_quillas_groups','five_quillas_matches','five_quillas_entries','five_quillas_phases','five_quillas_competitions','categories'] as $table) Schema::dropIfExists($table);
});

it('resolves first round byes and advances without proclaiming an undecided final', function () {
    app(FiveQuillasCompetitionService::class)->resolveBracketByes($this->phase);
    expect($this->bye->fresh()->status)->toBe('completed')
        ->and($this->bye->fresh()->winner_entry_id)->toBe($this->entries[0]->id)
        ->and($this->bye->fresh()->player_one_computed_sets)->toBe(3)
        ->and($this->final->fresh()->player_one_entry_id)->toBe($this->entries[0]->id)
        ->and($this->final->fresh()->status)->toBe('pending');
});

it('does not resolve missing participants as byes', function () {
    FiveQuillasEntry::create(['competition_id' => $this->competition->id]);
    app(FiveQuillasCompetitionService::class)->resolveBracketByes($this->phase);
    expect($this->bye->fresh()->status)->toBe('pending')
        ->and($this->final->fresh()->player_one_entry_id)->toBeNull();
});

it('waits until the previous phase is completed', function () {
    FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'sort_order' => 0, 'status' => 'in_progress']);
    app(FiveQuillasCompetitionService::class)->resolveBracketByes($this->phase);
    expect($this->bye->fresh()->status)->toBe('pending');
});

it('preserves played results and can be repeated safely', function () {
    $this->played->update(['status' => 'completed', 'winner_entry_id' => $this->entries[1]->id]);
    $this->final->update(['player_two_entry_id' => $this->entries[1]->id]);
    $before = $this->played->fresh()->getAttributes();
    $service = app(FiveQuillasCompetitionService::class);
    $service->resolveBracketByes($this->phase); $service->resolveBracketByes($this->phase);
    expect($this->played->fresh()->getAttributes())->toBe($before)
        ->and($this->final->fresh()->player_one_entry_id)->toBe($this->entries[0]->id)
        ->and($this->final->fresh()->player_two_entry_id)->toBe($this->entries[1]->id);
});

it('keeps finished zones open for missing entrants and closes them after withdrawal', function () {
    $this->phase->update(['phase_type' => 'zones', 'status' => 'in_progress']);
    FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'sort_order' => 2]);
    $this->competition->entries()->update(['entry_phase_id' => $this->phase->id, 'source_slot_id' => 1]);
    $group = \App\Models\FiveQuillasGroup::create(['phase_id' => $this->phase->id, 'status' => 'completed']);
    foreach ($this->entries->take(2) as $i => $entry) {
        \App\Models\FiveQuillasGroupEntry::create([
            'group_id' => $group->id, 'entry_id' => $entry->id, 'position' => $i + 1, 'final_place' => $i + 1,
        ]);
    }
    $this->phase->matches()->update(['status' => 'completed']);
    $before = $this->phase->matches()->get()->map->getAttributes()->all();
    $service = app(FiveQuillasCompetitionService::class);
    $service->reopenIncompleteConcurrentPhases($this->competition);
    expect($this->phase->fresh()->status)->toBe('in_progress');
    $this->entries[2]->update(['status' => 'withdrawn']);
    $service->reopenIncompleteConcurrentPhases($this->competition);
    expect($this->phase->fresh()->status)->toBe('completed')
        ->and($this->phase->matches()->get()->map->getAttributes()->all())->toBe($before);
    $service->reopenIncompleteConcurrentPhases($this->competition);
    expect($this->phase->fresh()->status)->toBe('completed');
});

it('assigns schedules without changing participants positions connections or phase settings', function () {
    $settings = ['tables' => 2, 'estimated_minutes' => 45, 'bracket_pairs' => [[4, 1], [2, 3]],
        'draw_seed_assignments' => [$this->entries[0]->id => 4],
        'schedules' => [['starts_at' => '2026-11-01 09:30:00', 'round_number' => 1]]];
    $this->phase->update(['settings' => $settings]);
    $fields = ['id','round_number','bracket_order','player_one_entry_id','player_two_entry_id',
        'winner_entry_id','next_match_id','next_match_position','is_bye','set_scores'];
    $before = $this->phase->matches()->get($fields)->toArray();
    $service = app(FiveQuillasCompetitionService::class);
    expect($service->scheduleConfiguredMatches($this->phase, 0))->toBe(2)
        ->and($this->phase->matches()->get($fields)->toArray())->toBe($before)
        ->and($this->phase->fresh()->settings)->toBe($settings)
        ->and($this->bye->fresh()->scheduled_at->format('H:i'))->toBe('09:30')
        ->and($this->played->fresh()->scheduled_at->format('H:i'))->toBe('09:30');
    $service->scheduleConfiguredMatches($this->phase, 0);
    expect($this->phase->matches()->get($fields)->toArray())->toBe($before);
});

it('preserves schedules and tables of matches already called started or completed', function ($status) {
    $this->phase->update(['settings' => ['tables' => 1, 'estimated_minutes' => 45,
        'schedules' => [['starts_at' => '2026-11-01 09:30:00']]]]);
    $this->played->update(['status' => $status, 'table_number' => 3, 'scheduled_at' => '2026-11-01 08:00:00']);
    $before = $this->played->fresh()->getAttributes();
    expect(app(FiveQuillasCompetitionService::class)->scheduleConfiguredMatches($this->phase, 0))->toBe(1)
        ->and($this->played->fresh()->getAttributes())->toBe($before);
})->with(['called', 'in_progress', 'completed']);

function configureResultsOrderFixture(): array
{
    $context = test();
    $previous = FiveQuillasPhase::create(['competition_id' => $context->competition->id,
        'phase_type' => 'zones', 'status' => 'completed', 'sort_order' => 0]);
    $entries = $context->entries->push(FiveQuillasEntry::create(['competition_id' => $context->competition->id]));
    foreach ($entries as $i => $entry) $entry->update(['entry_phase_id' => $previous->id, 'national_ranking_snapshot' => [100,1,200,2][$i]]);
    foreach ([[$entries[0],$entries[1]],[$entries[2],$entries[3]]] as $i => $groupEntries) {
        $group = \App\Models\FiveQuillasGroup::create(['phase_id' => $previous->id]);
        foreach ($groupEntries as $j => $entry) \App\Models\FiveQuillasGroupEntry::create([
            'group_id' => $group->id, 'entry_id' => $entry->id, 'position' => $j+1, 'final_place' => $j+1,
            'sets_difference' => [[2,6],[4,0]][$i][$j], 'points_average' => 1.2,
        ]);
    }
    $context->phase->update(['seeding_method' => 'previous_results', 'settings' => [
        'bracket_size' => 4, 'bracket_pairs' => [[1,4],[2,3]],
        'participant_seed_overrides' => [$entries[1]->id => 1],
    ]]);
    return $entries->all();
}

it('repairs an unplayed bracket by zone results rather than national ranking and preserves schedules', function () {
    $entries = configureResultsOrderFixture();
    $this->bye->update(['scheduled_at' => '2026-11-01 09:30:00', 'table_number' => 2]);
    $service = app(FiveQuillasCompetitionService::class);
    $rows = $service->bracketParticipantRows($this->phase);
    expect(array_column($rows, 'entry_id'))->toBe([$entries[2]->id,$entries[0]->id,$entries[1]->id,$entries[3]->id]);
    $ids = $this->phase->matches()->pluck('id')->all();
    $service->repairPreviousResultsBracket($this->phase);
    expect($this->bye->fresh()->player_one_entry_id)->toBe($entries[2]->id)
        ->and($this->bye->fresh()->player_two_entry_id)->toBe($entries[3]->id)
        ->and($this->played->fresh()->player_one_entry_id)->toBe($entries[0]->id)
        ->and($this->played->fresh()->player_two_entry_id)->toBe($entries[1]->id)
        ->and($this->bye->fresh()->scheduled_at->format('H:i'))->toBe('09:30')
        ->and($this->bye->fresh()->table_number)->toBe(2)
        ->and($this->phase->matches()->pluck('id')->all())->toBe($ids);
    $before = $this->phase->matches()->get()->toArray();
    $service->repairPreviousResultsBracket($this->phase);
    expect($this->phase->matches()->get()->toArray())->toBe($before);
});

it('does not automatically recalculate a bracket with a played match', function () {
    configureResultsOrderFixture();
    $this->played->update(['status' => 'completed', 'winner_entry_id' => $this->entries[1]->id]);
    $before = $this->phase->matches()->get()->toArray();
    app(FiveQuillasCompetitionService::class)->repairPreviousResultsBracket($this->phase);
    expect($this->phase->matches()->get()->toArray())->toBe($before);
});

function configureProgressiveDrawFixture(): array
{
    $context = test();
    $entries = configureResultsOrderFixture();
    $previous = $context->competition->phases()->where('phase_type', 'zones')->first();
    $previous->update(['status' => 'in_progress']);
    $previous->groups()->update(['status' => 'in_progress']);
    $context->phase->update(['seeding_method' => 'number_draw']);
    $context->phase->refresh();
    foreach ([$entries[0], $entries[2]] as $entry) {
        FiveQuillasMatch::create(['phase_id' => $previous->id, 'label' => 'Definición del primer puesto',
            'status' => 'completed', 'player_one_entry_id' => $entry->id, 'winner_entry_id' => $entry->id]);
    }
    return $entries;
}

it('places each drawn zone winner immediately while other zone matches remain unfinished', function () {
    $entries = configureProgressiveDrawFixture();
    $service = app(FiveQuillasCompetitionService::class);
    expect($service->drawCandidates($this->phase)->pluck('id')->all())->toBe([$entries[0]->id, $entries[2]->id]);
    $service->saveQualifiedDrawNumber($this->phase, $entries[0]->id, 3);
    expect(data_get($this->phase->fresh()->settings, 'draw_seed_assignments.'.$entries[0]->id))->toBe(3)
        ->and($this->played->fresh()->player_two_entry_id)->toBe($entries[0]->id);
    $service->saveQualifiedDrawNumber($this->phase, $entries[2]->id, 1);
    expect($this->bye->fresh()->player_one_entry_id)->toBe($entries[2]->id)
        ->and($this->played->fresh()->player_two_entry_id)->toBe($entries[0]->id)
        ->and(data_get($this->phase->fresh()->settings, 'draw_seed_assignments.'.$entries[0]->id))->toBe(3);
});

it('rejects duplicate draw numbers and preserves all existing assignments', function () {
    $entries = configureProgressiveDrawFixture();
    $service = app(FiveQuillasCompetitionService::class);
    $service->saveQualifiedDrawNumber($this->phase, $entries[0]->id, 3);
    $before = $this->phase->fresh()->settings;
    expect(fn () => $service->saveQualifiedDrawNumber($this->phase, $entries[2]->id, 3))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    expect($this->phase->fresh()->settings)->toBe($before)
        ->and($this->played->fresh()->player_two_entry_id)->toBe($entries[0]->id);
});

it('does not draw provisional qualifiers or positions outside the bracket', function () {
    $entries = configureProgressiveDrawFixture();
    $service = app(FiveQuillasCompetitionService::class);
    expect(fn () => $service->saveQualifiedDrawNumber($this->phase, $entries[1]->id, 2))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    expect(fn () => $service->saveQualifiedDrawNumber($this->phase, $entries[0]->id, 5))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('protects a bracket that already has called or played matches from draw edits', function () {
    $entries = configureProgressiveDrawFixture();
    $this->played->update(['status' => 'called']);
    expect(fn () => app(FiveQuillasCompetitionService::class)->saveQualifiedDrawNumber($this->phase, $entries[0]->id, 3))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

it('recovers all 48 final positions when only the 24 bracket players retained theirs', function (int $version) {
    $this->competition->update(['status' => 'completed', 'settings' => ['standings_order_version' => $version]]);
    $this->phase->matches()->delete();
    $this->phase->update(['status' => 'completed', 'sort_order' => 2, 'settings' => ['bracket_size' => 32]]);
    $zones = FiveQuillasPhase::create(['competition_id' => $this->competition->id,
        'phase_type' => 'zones', 'sort_order' => 1, 'status' => 'completed']);
    $entries = $this->entries;
    for ($i = $entries->count(); $i < 48; $i++) {
        $entries->push(FiveQuillasEntry::create(['competition_id' => $this->competition->id]));
    }
    foreach ($entries as $index => $entry) {
        $entry->update(['entry_phase_id' => $zones->id, 'final_position' => $index < 24 ? $index + 1 : null]);
    }
    for ($i = 0; $i < 12; $i++) {
        $group = \App\Models\FiveQuillasGroup::create(['phase_id' => $zones->id, 'status' => 'completed']);
        foreach ([$entries[$i * 2], $entries[$i * 2 + 1], $entries[24 + $i], $entries[36 + $i]] as $place => $entry) {
            \App\Models\FiveQuillasGroupEntry::create(['group_id' => $group->id, 'entry_id' => $entry->id,
                'position' => $place + 1, 'final_place' => $place + 1, 'sets_difference' => -$place]);
        }
    }
    for ($remaining = 2, $round = 5; $remaining <= 32; $remaining *= 2, $round--) {
        for ($winner = 0; $winner < $remaining / 2; $winner++) {
            $loser = $winner + $remaining / 2;
            FiveQuillasMatch::create(['phase_id' => $this->phase->id, 'round_number' => $round,
                'player_one_entry_id' => $entries[$winner]->id,
                'player_two_entry_id' => $loser < 24 ? $entries[$loser]->id : null,
                'winner_entry_id' => $entries[$winner]->id, 'status' => 'completed', 'is_bye' => $loser >= 24]);
        }
    }
    $withdrawn = FiveQuillasEntry::create(['competition_id' => $this->competition->id, 'status' => 'withdrawn']);
    $service = app(FiveQuillasCompetitionService::class);
    $service->ensureFinalStandingsOrder($this->competition->fresh());
    $result = $this->competition->entries()->whereNotNull('final_position')->orderBy('final_position')->get();
    expect($result->count())->toBe(48)
        ->and($result->pluck('final_position')->all())->toBe(range(1, 48))
        ->and($result->take(24)->pluck('id')->all())->toBe($entries->take(24)->pluck('id')->all())
        ->and($result->slice(24)->pluck('id')->all())->toBe($entries->slice(24)->pluck('id')->all())
        ->and($withdrawn->fresh()->final_position)->toBeNull();
    $before = $this->competition->entries()->get()->map->getAttributes()->all();
    $service->ensureFinalStandingsOrder($this->competition->fresh());
    expect($this->competition->entries()->get()->map->getAttributes()->all())->toBe($before);
})->with([4, 5]);

it('applies the Argentine circuit over an automatically generated empty bracket', function () {
    FiveQuillasMatch::query()->delete();
    $this->phase->update(['settings' => ['bracket_size' => 16]]);
    $service = Mockery::mock(FiveQuillasCompetitionService::class)->makePartial();
    $service->shouldReceive('syncApprovedEntries')->once()->andReturn(0);
    $service->ensureBracketSkeleton($this->phase);
    expect($this->phase->matches()->count())->toBe(15);
    $this->competition->setRelation('tournament', new \App\Models\Tournament(['discipline_id' => 1]));
    foreach ([['S', 'Segunda'], ['PN', 'Primera Nacional'], ['M', 'Master']] as [$code, $name]) {
        \App\Models\Category::query()->insert(['code' => $code, 'name' => $name, 'discipline_id' => 1]);
    }

    expect($service->applyCompetitionTemplate($this->competition, 'circuit_argentino'))->toBe(5);
    $phases = $this->competition->phases()->orderBy('sort_order')->get();
    expect($phases->pluck('code')->all())->toBe(['zones', 'slot-elimination', 'national-play-in', 'national', 'master-final'])
        ->and($phases[0]->settings['entry_category_ids'])->toBe([1])
        ->and($phases[3]->settings['entry_category_ids'])->toBe([2])
        ->and($phases[4]->settings['entry_category_ids'])->toBe([3])
        ->and($this->competition->fresh()->settings['format'])->toBe('circuit_argentino');
});

it('preserves a template with sporting or scheduling data', function (array $attributes) {
    FiveQuillasMatch::query()->delete();
    $match = FiveQuillasMatch::create(array_merge(['phase_id' => $this->phase->id], $attributes));
    $service = app(FiveQuillasCompetitionService::class);
    expect(fn () => $service->applyCompetitionTemplate($this->competition, 'circuit_argentino'))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
    expect($match->fresh())->not->toBeNull()
        ->and($this->phase->fresh())->not->toBeNull();
})->with([
    'player assigned' => [['player_one_entry_id' => 1]],
    'scheduled' => [['scheduled_at' => '2026-11-03 10:00:00']],
    'table assigned' => [['table_number' => 2]],
    'called' => [['status' => 'called']],
    'scores' => [['set_scores' => [['player_one' => 10, 'player_two' => 5]]]],
]);
