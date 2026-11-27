<?php

use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasEntry;
use App\Models\FiveQuillasGroup;
use App\Models\FiveQuillasGroupEntry;
use App\Models\FiveQuillasMatch;
use App\Models\FiveQuillasPhase;
use App\Services\FiveQuillasCompetitionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Schema::create('five_quillas_competitions', function (Blueprint $t) {
        $t->id(); $t->string('status')->default('draft'); $t->timestamps();
    });
    Schema::create('five_quillas_phases', function (Blueprint $t) {
        $t->id(); $t->foreignId('competition_id')->constrained('five_quillas_competitions');
        $t->string('phase_type'); $t->boolean('is_enabled')->default(true); $t->string('status')->default('ready'); $t->timestamps();
    });
    Schema::create('five_quillas_entries', function (Blueprint $t) {
        $t->id(); $t->foreignId('competition_id')->constrained('five_quillas_competitions');
        $t->integer('player_id'); $t->integer('national_ranking_snapshot'); $t->string('status'); $t->timestamps();
    });
    Schema::create('five_quillas_groups', function (Blueprint $t) {
        $t->id(); $t->foreignId('phase_id')->constrained('five_quillas_phases'); $t->timestamps();
    });
    Schema::create('five_quillas_group_entries', function (Blueprint $t) {
        $t->id(); $t->foreignId('group_id')->constrained('five_quillas_groups');
        $t->foreignId('entry_id')->constrained('five_quillas_entries'); $t->integer('position'); $t->timestamps();
    });
    Schema::create('five_quillas_matches', function (Blueprint $t) {
        $t->id(); $t->foreignId('phase_id')->constrained('five_quillas_phases');
        $t->foreignId('group_id')->nullable()->constrained('five_quillas_groups');
        foreach (['player_one_entry_id', 'player_two_entry_id', 'winner_entry_id', 'next_match_id', 'loser_next_match_id'] as $field) $t->unsignedBigInteger($field)->nullable();
        foreach (['player_one_points', 'player_two_points', 'player_one_actual_sets', 'player_two_actual_sets'] as $field) $t->integer($field)->default(0);
        $t->string('status')->default('pending'); $t->boolean('is_bye')->default(false); $t->json('set_scores')->nullable(); $t->timestamps();
    });
    $this->competition = FiveQuillasCompetition::create([]);
    $this->zones = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'phase_type' => 'zones']);
    $this->bracket = FiveQuillasPhase::create(['competition_id' => $this->competition->id, 'phase_type' => 'bracket']);
    $this->entry = FiveQuillasEntry::create(['competition_id' => $this->competition->id, 'player_id' => 91, 'national_ranking_snapshot' => 37, 'status' => 'assigned']);
    $this->group = FiveQuillasGroup::create(['phase_id' => $this->zones->id]);
    FiveQuillasGroupEntry::create(['group_id' => $this->group->id, 'entry_id' => $this->entry->id, 'position' => 1]);
    $this->match = FiveQuillasMatch::create(['phase_id' => $this->zones->id, 'group_id' => $this->group->id, 'player_one_entry_id' => $this->entry->id]);
});

afterEach(function () {
    foreach (['five_quillas_matches', 'five_quillas_group_entries', 'five_quillas_groups', 'five_quillas_entries', 'five_quillas_phases', 'five_quillas_competitions'] as $table) Schema::dropIfExists($table);
});

it('clears unstarted zones and preserves players, ranking, other phases and other tournaments', function () {
    $other = FiveQuillasCompetition::create([]);
    $otherPhase = FiveQuillasPhase::create(['competition_id' => $other->id, 'phase_type' => 'zones']);
    $otherGroup = FiveQuillasGroup::create(['phase_id' => $otherPhase->id]);
    $bracketMatch = FiveQuillasMatch::create(['phase_id' => $this->bracket->id]);
    $service = app(FiveQuillasCompetitionService::class);

    expect($service->clearUnstartedZones($this->competition))->toBe(1)
        ->and($this->zones->groups()->count())->toBe(0)
        ->and($this->zones->matches()->count())->toBe(0)
        ->and(FiveQuillasGroupEntry::count())->toBe(0)
        ->and($this->entry->fresh()->status)->toBe('eligible')
        ->and($this->entry->fresh()->player_id)->toBe(91)
        ->and($this->entry->fresh()->national_ranking_snapshot)->toBe(37)
        ->and($this->zones->fresh()->status)->toBe('pending')
        ->and($bracketMatch->fresh())->not->toBeNull()
        ->and($otherGroup->fresh())->not->toBeNull()
        ->and($service->clearUnstartedZones($this->competition))->toBe(0);
});

it('refuses to clear when sporting activity exists and leaves the zones intact', function (array $changes) {
    $this->match->update($changes);
    expect(fn () => app(FiveQuillasCompetitionService::class)->clearUnstartedZones($this->competition))->toThrow(ValidationException::class);
    expect($this->group->fresh())->not->toBeNull()
        ->and($this->match->fresh())->not->toBeNull()
        ->and($this->entry->fresh()->status)->toBe('assigned');
})->with([
    [['status' => 'called']], [['status' => 'in_progress']], [['status' => 'suspended']],
    [['status' => 'completed']], [['player_one_points' => 10]],
    [['set_scores' => ['sets' => [['player_one_points' => 60, 'player_two_points' => 20]]]]],
]);

it('refuses to clear players already assigned to a following phase', function () {
    FiveQuillasMatch::create(['phase_id' => $this->bracket->id, 'player_one_entry_id' => $this->entry->id]);
    expect(fn () => app(FiveQuillasCompetitionService::class)->clearUnstartedZones($this->competition))->toThrow(ValidationException::class);
    expect($this->group->fresh())->not->toBeNull();
});

it('allows automatic byes and links within the zones to be discarded before play', function () {
    $next = FiveQuillasMatch::create(['phase_id' => $this->zones->id, 'group_id' => $this->group->id]);
    $this->match->update(['is_bye' => true, 'status' => 'completed', 'winner_entry_id' => $this->entry->id, 'next_match_id' => $next->id]);
    expect(app(FiveQuillasCompetitionService::class)->clearUnstartedZones($this->competition))->toBe(1)
        ->and($this->zones->matches()->count())->toBe(0);
});

it('blocks links to a following phase even without players assigned', function () {
    $next = FiveQuillasMatch::create(['phase_id' => $this->bracket->id]);
    $this->match->update(['next_match_id' => $next->id]);
    expect(fn () => app(FiveQuillasCompetitionService::class)->clearUnstartedZones($this->competition))->toThrow(ValidationException::class);
    expect($this->match->fresh()->next_match_id)->toBe($next->id);
});
