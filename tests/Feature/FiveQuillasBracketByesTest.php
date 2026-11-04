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
        foreach (['player_one_entry_id','player_two_entry_id','winner_entry_id','next_match_id','loser_next_match_id','next_match_position','loser_next_match_position'] as $key) $t->unsignedBigInteger($key)->nullable();
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
    $emptyOrphan = FiveQuillasMatch::create(['phase_id' => $this->phase->id, 'status' => 'pending',
        'player_one_entry_id' => $this->entries[0]->id, 'player_two_entry_id' => $this->entries[1]->id]);
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
    expect($emptyOrphan->fresh()->status)->toBe('pending');
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
    foreach ([['S', 'Segunda'], ['PN', 'Primera Nacional'], ['M', 'Master'], ['P', 'Primera']] as [$code, $name]) {
        \App\Models\Category::query()->insert(['code' => $code, 'name' => $name, 'discipline_id' => 1]);
    }

    expect($service->applyCompetitionTemplate($this->competition, 'circuit_argentino'))->toBe(5);
    $phases = $this->competition->phases()->orderBy('sort_order')->get();
    expect($phases->pluck('code')->all())->toBe(['zones', 'slot-elimination', 'national-play-in', 'national', 'master-final'])
        ->and($phases[0]->settings['entry_category_ids'])->toBe([1, 4])
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

it('assigns national slots to the national bracket instead of the play in and keeps Primera in zones', function () {
    foreach ([['P', 'Primera'], ['N', 'Primera Nacional'], ['M', 'Master']] as $index => [$code, $name]) {
        \App\Models\Category::query()->insert(['id' => $index + 1, 'code' => $code, 'name' => $name, 'discipline_id' => 1]);
    }
    $zones = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'code' => 'zones', 'phase_type' => 'zones', 'settings' => ['entry_category_ids' => [1]]]);
    $playIn = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'code' => 'national-play-in', 'name' => 'Emparejamientos de Primera Nacional', 'phase_type' => 'play_in']);
    $national = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'code' => 'national', 'name' => 'Primera Nacional', 'phase_type' => 'bracket', 'settings' => ['entry_category_ids' => [2]]]);
    $master = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'code' => 'master-final', 'name' => 'Master', 'phase_type' => 'bracket', 'settings' => ['entry_category_ids' => [3]]]);
    $phases = collect([$zones, $playIn, $national, $master]);
    $method = new ReflectionMethod(FiveQuillasCompetitionService::class, 'entryPhaseForRegistration');
    $service = app(FiveQuillasCompetitionService::class);
    $primera = new \App\Models\Player(['category_id' => 1]);
    expect($method->invoke($service, $phases, $primera, 'NACIONALES - 10:00')->id)->toBe($national->id)
        ->and($method->invoke($service, $phases, $primera, 'PRIMERA - 10:00')->id)->toBe($zones->id)
        ->and($method->invoke($service, $phases, $primera, 'MASTERS - 18:00')->id)->toBe($master->id);
});

it('disables an unnecessary circuit play in only after all 32 qualifiers are confirmed', function () {
    FiveQuillasMatch::query()->delete();
    $this->competition->update(['settings' => ['format' => 'circuit_argentino']]);
    $this->phase->update(['phase_type' => 'slot_elimination', 'status' => 'in_progress']);
    $ids = collect(range(1,32))->map(fn () => FiveQuillasEntry::create(['competition_id' => $this->competition->id])->id)->all();
    $this->phase->update(['settings' => ['qualifiers' => 32, 'qualified_entry_ids' => $ids]]);
    foreach ($ids as $id) FiveQuillasMatch::create(['phase_id' => $this->phase->id, 'status' => 'completed', 'winner_entry_id' => $id]);
    $playIn = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'code' => 'national-play-in', 'phase_type' => 'play_in', 'sort_order' => 2, 'is_enabled' => true]);
    $national = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'code' => 'national', 'phase_type' => 'bracket', 'sort_order' => 3]);
    $service = app(FiveQuillasCompetitionService::class);
    $service->skipUnnecessaryCircuitPlayIn($this->competition);
    expect($playIn->fresh()->is_enabled)->toBeTrue();
    $this->phase->update(['status' => 'completed']);
    $lastCrossing = $this->phase->matches()->latest('id')->first();
    $lastCrossing->update(['status' => 'pending']);
    $service->skipUnnecessaryCircuitPlayIn($this->competition);
    expect($playIn->fresh()->is_enabled)->toBeTrue();
    $lastCrossing->update(['status' => 'completed']);
    $service->skipUnnecessaryCircuitPlayIn($this->competition);
    expect($playIn->fresh()->is_enabled)->toBeFalse()
        ->and($playIn->fresh()->settings['qualified_entry_ids'])->toBe($ids)
        ->and((new ReflectionMethod($service, 'previousPhase'))->invoke($service, $national)->id)->toBe($this->phase->id);
});

it('creates only eight play in matches for forty qualifiers and advances twenty four byes plus eight winners', function () {
    FiveQuillasMatch::query()->delete();
    $this->competition->update(['settings' => ['format' => 'circuit_argentino']]);
    $this->phase->update(['phase_type' => 'slot_elimination', 'status' => 'completed']);
    $qualified = collect(range(1,40))->map(fn ($rank) => FiveQuillasEntry::create([
        'competition_id' => $this->competition->id, 'entry_phase_id' => $this->phase->id,
        'national_ranking_snapshot' => $rank, 'status' => 'qualified',
    ]));
    $this->phase->update(['settings' => ['qualifiers' => 40, 'qualified_entry_ids' => $qualified->pluck('id')->all()]]);
    foreach ($qualified as $entry) FiveQuillasMatch::create(['phase_id' => $this->phase->id, 'status' => 'completed', 'winner_entry_id' => $entry->id]);
    $playIn = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'code' => 'national-play-in', 'phase_type' => 'play_in', 'sort_order' => 2, 'is_enabled' => true, 'settings' => ['qualifiers' => 32]]);
    $service = app(FiveQuillasCompetitionService::class);
    $service->skipUnnecessaryCircuitPlayIn($this->competition);
    $matches = $playIn->matches()->orderBy('bracket_order')->get();
    expect($matches)->toHaveCount(8)
        ->and($playIn->fresh()->is_enabled)->toBeTrue()
        ->and($playIn->fresh()->settings['play_in_bye_entry_ids'])->toBe($qualified->take(24)->pluck('id')->all());
    foreach ($matches as $i => $match) {
        expect($match->player_one_entry_id)->toBe($qualified[24+$i]->id)
            ->and($match->player_two_entry_id)->toBe($qualified[39-$i]->id);
        $match->update(['winner_entry_id' => $match->player_two_entry_id, 'status' => 'completed']);
    }
    $service->skipUnnecessaryCircuitPlayIn($this->competition);
    expect($playIn->matches()->count())->toBe(8);
    $playIn->update(['status' => 'completed']);
    $advancing = (new ReflectionMethod($service, 'qualifiedEntriesFromPhase'))->invoke($service, $playIn->fresh());
    $expected = $qualified->take(24)->pluck('id')->merge($matches->pluck('player_two_entry_id'))->sort()->values()->all();
    expect($advancing)->toHaveCount(32)
        ->and($advancing->pluck('id')->sort()->values()->all())->toBe($expected);
});

it('keeps only direct masters until the whole national phase finishes even with progressive filling', function () {
    FiveQuillasMatch::query()->delete();
    $this->competition->update(['settings' => ['format' => 'circuit_argentino']]);
    $qualified = $this->entries->take(2);
    $this->phase->update(['code' => 'national', 'name' => 'Nacional', 'status' => 'in_progress', 'settings' => [
        'qualifiers' => 2, 'qualified_entry_ids' => $qualified->pluck('id')->all(),
    ]]);
    $nationalMatches = $qualified->map(fn ($entry) => FiveQuillasMatch::create([
        'phase_id' => $this->phase->id, 'status' => 'completed', 'winner_entry_id' => $entry->id,
    ]));
    $master = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'code' => 'master-final',
        'name' => 'Masters', 'phase_type' => 'bracket', 'sort_order' => 2, 'seeding_method' => 'national_ranking',
        'settings' => ['bracket_size' => 4, 'direct_entry_seed_count' => 2, 'progressive_fill' => true],
    ]);
    $direct = collect(range(1,2))->map(fn ($rank) => FiveQuillasEntry::create([
        'competition_id' => $this->competition->id, 'entry_phase_id' => $master->id, 'status' => 'eligible', 'national_ranking_snapshot' => $rank,
    ]));
    $service = Mockery::mock(FiveQuillasCompetitionService::class)->makePartial();
    $all = $direct->concat($qualified)->values();
    $service->shouldReceive('bracketParticipantRows')->andReturn($all->map(fn ($entry, $i) => [
        'entry_id' => $entry->id, 'seed_position' => $i+1,
    ])->all());
    $service->ensureBracketSkeleton($master);
    $assigned = fn () => $master->matches()->where('round_number', 1)->get()->flatMap(fn ($match) => [
        $match->player_one_entry_id, $match->player_two_entry_id,
    ])->filter()->sort()->values()->all();
    expect($assigned())->toBe($direct->pluck('id')->sort()->values()->all())
        ->and(fn () => $service->preparePhase($master->fresh()))->toThrow(\Illuminate\Validation\ValidationException::class);
    $this->phase->update(['status' => 'completed']);
    $nationalMatches->last()->update(['status' => 'in_progress']);
    expect(fn () => $service->preparePhase($master->fresh()))->toThrow(\Illuminate\Validation\ValidationException::class);
    $nationalMatches->last()->update(['status' => 'completed']);
    $service->ensureBracketSkeleton($master->fresh());
    expect($assigned())->toBe($all->pluck('id')->sort()->values()->all());
});

it('loads historical Masters from their inactive slot despite a different current ranking and protects played matches', function () {
    Schema::create('players', function (Blueprint $t) { $t->id(); });
    Schema::create('general_rankings', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('player_id'); $t->string('category'); $t->integer('RG'); });
    Schema::create('tournament_slots', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('tournament_id'); $t->string('name'); $t->boolean('is_active'); });
    Schema::create('tournament_registration_participants', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('tournament_registration_id'); $t->unsignedBigInteger('player_id'); $t->integer('position'); });
    Schema::table('tournament_registrations', function (Blueprint $t) { $t->unsignedBigInteger('tournament_id')->nullable(); $t->unsignedBigInteger('tournament_slot_id')->nullable(); $t->unsignedBigInteger('player_id')->nullable(); $t->string('status')->nullable(); });
    Schema::table('five_quillas_entries', function (Blueprint $t) { $t->unsignedBigInteger('registration_participant_id')->nullable(); });
    try {
        FiveQuillasMatch::query()->delete();
        $this->competition->update(['tournament_id' => 1, 'settings' => ['format' => 'circuit_argentino']]);
        Schema::create('tournaments', function (Blueprint $t) { $t->id(); });
        \Illuminate\Support\Facades\DB::table('tournaments')->insert(['id' => 1]);
        \Illuminate\Support\Facades\DB::table('tournament_slots')->insert(['id' => 1, 'tournament_id' => 1, 'name' => 'MASTERS', 'is_active' => false]);
        $this->phase->update(['code' => 'national', 'status' => 'pending', 'settings' => ['qualifiers' => 16]]);
        $master = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'code' => 'master-final',
            'phase_type' => 'bracket', 'sort_order' => 2, 'seeding_method' => 'national_ranking',
            'settings' => ['bracket_size' => 32, 'direct_entry_seed_count' => 16],
        ]);
        foreach (range(1,32) as $id) {
            \Illuminate\Support\Facades\DB::table('players')->insert(['id' => $id]);
            \Illuminate\Support\Facades\DB::table('general_rankings')->insert(['player_id' => $id, 'category' => $id <= 16 ? 'P' : 'M', 'RG' => 100+33-$id]);
            if ($id <= 16) {
                \Illuminate\Support\Facades\DB::table('tournament_registrations')->insert(['id' => $id, 'tournament_id' => 1, 'tournament_slot_id' => 1, 'player_id' => $id, 'status' => 'aprobado']);
                \Illuminate\Support\Facades\DB::table('tournament_registration_participants')->insert(['tournament_registration_id' => $id, 'player_id' => $id, 'position' => 1]);
            }
        }
        $service = app(FiveQuillasCompetitionService::class);
        $misplaced = FiveQuillasEntry::create(['competition_id' => $this->competition->id,
            'entry_phase_id' => $this->phase->id, 'player_id' => 16, 'national_ranking_snapshot' => 1]);
        $group = \App\Models\FiveQuillasGroup::create(['phase_id' => $this->phase->id, 'status' => 'draft']);
        \App\Models\FiveQuillasGroupEntry::create(['group_id' => $group->id, 'entry_id' => $misplaced->id, 'position' => 1]);
        $otherMatch = FiveQuillasMatch::create(['phase_id' => $this->phase->id, 'group_id' => $group->id,
            'player_one_entry_id' => $misplaced->id, 'status' => 'in_progress']);
        expect(fn () => $service->loadRegisteredMasters($master))->toThrow(\Illuminate\Validation\ValidationException::class);
        expect($misplaced->fresh()->entry_phase_id)->toBe($this->phase->id)->and($group->groupEntries()->count())->toBe(1);
        $otherMatch->update(['status' => 'pending']);
        expect($service->loadRegisteredMasters($master))->toBe(16);
        expect($otherMatch->fresh()->player_one_entry_id)->toBeNull()->and($group->groupEntries()->count())->toBe(0);
        $entries = $this->competition->entries()->where('entry_phase_id', $master->id)->orderBy('seed')->get();
        $assigned = $master->matches()->where('round_number', 1)->get()->flatMap(fn ($match) => [
            $match->player_one_entry_id, $match->player_two_entry_id,
        ])->filter()->unique();
        expect($assigned)->toHaveCount(16)
            ->and($entries->pluck('player_id')->sort()->values()->all())->toBe(range(1,16))
            ->and($entries->first()->player_id)->toBe(16)
            ->and($entries->first()->national_ranking_snapshot)->toBe(1)
            ->and($entries->where('player_id', 1)->first()->national_ranking_snapshot)->toBe(132)
            ->and($entries->pluck('player_id')->all())->toBe(range(16,1))
            ->and($entries->pluck('tournament_registration_id')->sort()->values()->all())->toBe(range(1,16))
            ->and($this->phase->fresh()->status)->toBe('pending');
        \Illuminate\Support\Facades\DB::table('general_rankings')->update(['RG' => 999]);
        $service->loadRegisteredMasters($master->fresh());
        expect($this->competition->entries()->where('entry_phase_id', $master->id)->count())->toBe(16);
        // An absent registration created after the bracket is already present
        // must join National when the organization page synchronizes again.
        \Illuminate\Support\Facades\DB::table('players')->insert(['id' => 33]);
        \Illuminate\Support\Facades\DB::table('general_rankings')->insert(['player_id' => 33, 'category' => 'N', 'RG' => 200]);
        \Illuminate\Support\Facades\DB::table('tournament_slots')->insert(['id' => 2, 'tournament_id' => 1, 'name' => 'NACIONALES', 'is_active' => true]);
        \Illuminate\Support\Facades\DB::table('tournament_registrations')->insert(['id' => 33, 'tournament_id' => 1, 'tournament_slot_id' => 2, 'player_id' => 33, 'status' => 'ausente']);
        \Illuminate\Support\Facades\DB::table('tournament_registration_participants')->insert(['tournament_registration_id' => 33, 'player_id' => 33, 'position' => 1]);
        expect($service->syncApprovedEntries($this->competition))->toBe(17);
        $absentEntry = $this->competition->entries()->where('player_id', 33)->firstOrFail();
        expect($absentEntry->entry_phase_id)->toBe($this->phase->id)
            ->and($absentEntry->status)->toBe('eligible')
            ->and($absentEntry->national_ranking_snapshot)->toBe(200);
        \Illuminate\Support\Facades\DB::table('general_rankings')->where('player_id', 33)->update(['RG' => 500]);
        $service->syncApprovedEntries($this->competition);
        expect($absentEntry->fresh()->national_ranking_snapshot)->toBe(200);
        $master->matches()->first()->update(['status' => 'called']);
        expect(fn () => $service->loadRegisteredMasters($master->fresh()))->toThrow(\Illuminate\Validation\ValidationException::class);
    } finally {
        foreach (['tournament_registration_participants', 'tournament_slots', 'general_rankings', 'players', 'tournaments'] as $table) Schema::dropIfExists($table);
    }
});

it('keeps an absent national in the bracket and advances the rival with a phase based penalty exactly once', function () {
    Schema::create('tournament_types', function (Blueprint $t) { $t->id(); $t->boolean('assigns_points'); $t->boolean('affects_ranking'); $t->string('scoring_method'); $t->json('scoring_rules'); });
    Schema::create('tournaments', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('tournament_type_id'); $t->unsignedBigInteger('discipline_id')->nullable(); });
    Schema::table('tournament_registrations', function (Blueprint $t) {
        $t->unsignedBigInteger('tournament_id')->nullable(); $t->string('status')->nullable(); $t->integer('penalty_points')->default(0);
        $t->string('result_code')->nullable(); $t->string('result_description')->nullable(); $t->integer('result_instance_value')->nullable();
        $t->unsignedBigInteger('tournament_instance_id')->nullable(); $t->timestamps();
    });
    try {
        $db = \Illuminate\Support\Facades\DB::class;
        $db::table('tournament_types')->insert(['id' => 1, 'assigns_points' => true, 'affects_ranking' => false, 'scoring_method' => 'position',
            'scoring_rules' => json_encode([['code' => 'F1 NAC', 'description' => 'F1 NAC', 'instance_value' => 32, 'points' => 10]])]);
        $db::table('tournaments')->insert(['id' => 1, 'tournament_type_id' => 1]);
        $db::table('tournament_registrations')->insert([
            ['id' => 1, 'tournament_id' => 1, 'status' => 'ausente'],
            ['id' => 2, 'tournament_id' => 1, 'status' => 'aprobado'],
        ]);
        $this->competition->update(['tournament_id' => 1]);
        $this->phase->update(['code' => 'national', 'name' => 'Primera Nacional', 'settings' => ['bracket_size' => 4]]);
        $this->entries[1]->update(['tournament_registration_id' => 1]);
        $this->entries[2]->update(['tournament_registration_id' => 2]);
        $previous = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'sort_order' => 0, 'status' => 'pending']);
        $service = app(FiveQuillasCompetitionService::class);
        expect($service->resolveAbsentMatch($this->played))->toBeFalse();
        $absentBye = FiveQuillasMatch::create(['phase_id' => $this->phase->id,
            'player_one_entry_id' => $this->entries[1]->id]);
        (new ReflectionMethod($service, 'resolveBye'))->invoke($service, $absentBye);
        expect($absentBye->fresh()->winner_entry_id)->toBeNull();
        $previous->update(['status' => 'completed']);
        $db::table('tournament_registrations')->where('id', 2)->update(['status' => 'ausente']);
        expect($service->resolveAbsentMatch($this->played))->toBeFalse();
        $db::table('tournament_registrations')->where('id', 2)->update(['status' => 'aprobado']);
        expect($service->resolveAbsentMatch($this->played))->toBeTrue()
            ->and($this->played->fresh()->winner_entry_id)->toBe($this->entries[2]->id)
            ->and($this->played->fresh()->player_one_entry_id)->toBe($this->entries[1]->id)
            ->and($this->played->fresh()->is_bye)->toBeFalse()
            ->and($this->final->fresh()->player_two_entry_id)->toBe($this->entries[2]->id);
        $registration = \App\Models\TournamentRegistration::findOrFail(1);
        expect((float) $registration->points)->toBe(0.0)
            ->and((int) $registration->penalty_points)->toBe(10)
            ->and($registration->status)->toBe('ausente')
            ->and($registration->result_code)->toBe('F1 NAC')
            ->and($service->resolveAbsentMatch($this->played))->toBeFalse()
            ->and((int) $registration->fresh()->penalty_points)->toBe(10);
    } finally {
        Schema::dropIfExists('tournaments');
        Schema::dropIfExists('tournament_types');
    }
});

it('trims an existing national bracket to two rounds for sixteen qualifiers without changing valid results', function () {
    FiveQuillasMatch::query()->delete();
    $this->phase->update(['code' => 'national', 'name' => 'Primera Nacional', 'settings' => ['bracket_size' => 64]]);
    $service = app(FiveQuillasCompetitionService::class);
    (new ReflectionMethod($service, 'createEliminationBracket'))->invoke($service, $this->phase, [], false);
    expect($this->phase->matches()->count())->toBe(63);
    $first = $this->phase->matches()->where('round_number', 1)->first();
    $first->update(['player_one_entry_id' => $this->entries[0]->id, 'player_two_entry_id' => $this->entries[1]->id,
        'winner_entry_id' => $this->entries[0]->id, 'status' => 'completed', 'scheduled_at' => '2026-11-03 10:00:00', 'table_number' => 4]);
    $before = $first->fresh()->getAttributes();
    $this->phase->update(['settings' => ['bracket_size' => 64, 'qualifiers' => 16, 'stop_after_rounds' => 6]]);
    $extra = $this->phase->matches()->where('round_number', 3)->first();
    $extra->update(['status' => 'called']);
    expect(fn () => $service->reconcileBracketRounds($this->phase))->toThrow(\Illuminate\Validation\ValidationException::class);
    expect($this->phase->matches()->count())->toBe(63);
    $extra->update(['status' => 'pending']);
    $service->reconcileBracketRounds($this->phase);
    expect($this->phase->matches()->count())->toBe(48)
        ->and($this->phase->matches()->max('round_number'))->toBe(2)
        ->and($this->phase->matches()->where('round_number', 2)->whereNotNull('next_match_id')->count())->toBe(0)
        ->and($first->fresh()->getAttributes())->toBe($before);
    $service->reconcileBracketRounds($this->phase);
    expect($this->phase->matches()->count())->toBe(48);
});
