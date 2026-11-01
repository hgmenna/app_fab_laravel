<?php

use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasEntry;
use App\Models\FiveQuillasMatch;
use App\Models\FiveQuillasPhase;
use App\Services\FiveQuillasCompetitionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('five_quillas_competitions', function (Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('tournament_id')->nullable(); $t->timestamps();
    });
    Schema::create('five_quillas_phases', function (Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('competition_id'); $t->string('phase_type')->default('bracket');
        $t->integer('sort_order')->default(1); $t->boolean('is_enabled')->default(true);
        $t->integer('sets_to_play')->default(3); $t->string('status')->default('ready');
        $t->string('seeding_method')->default('number_draw'); $t->json('settings')->nullable(); $t->timestamps();
    });
    Schema::create('five_quillas_entries', function (Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('competition_id'); $t->unsignedBigInteger('entry_phase_id')->nullable();
        $t->unsignedBigInteger('player_id')->nullable(); $t->string('status')->default('assigned'); $t->timestamps();
    });
    Schema::create('five_quillas_matches', function (Blueprint $t) {
        $t->id(); $t->unsignedBigInteger('phase_id'); $t->integer('round_number')->default(1);
        $t->integer('bracket_order')->default(1);
        foreach (['player_one_entry_id','player_two_entry_id','winner_entry_id','next_match_id','loser_next_match_id','next_match_position'] as $key) $t->unsignedBigInteger($key)->nullable();
        foreach (['player_one_actual_sets','player_two_actual_sets','player_one_computed_sets','player_two_computed_sets'] as $key) $t->integer($key)->default(0);
        $t->json('set_scores')->nullable(); $t->boolean('is_bye')->default(false);
        $t->string('status')->default('pending'); $t->timestamps();
    });
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
    foreach (['five_quillas_matches','five_quillas_entries','five_quillas_phases','five_quillas_competitions'] as $table) Schema::dropIfExists($table);
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
