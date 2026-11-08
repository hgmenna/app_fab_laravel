<?php

use App\Helpers\CompetitionViewVersion;
use App\Livewire\CompetitionOrganizationUpdates;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);

    Schema::create('five_quillas_competitions', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tournament_id');
    });
    Schema::create('five_quillas_phases', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('competition_id');
    });
    Schema::create('five_quillas_matches', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('phase_id');
        $table->string('status')->default('pending');
        $table->json('set_scores')->nullable();
        $table->unsignedBigInteger('winner_entry_id')->nullable();
        $table->boolean('is_bye')->default(false);
        $table->unsignedBigInteger('player_one_entry_id')->nullable();
        $table->unsignedBigInteger('player_two_entry_id')->nullable();
        $table->dateTime('scheduled_at')->nullable();
        $table->unsignedInteger('table_number')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('five_quillas_matches');
    Schema::dropIfExists('five_quillas_phases');
    Schema::dropIfExists('five_quillas_competitions');
});

it('detects saved scores even when no cache notification is published', function () {
    DB::table('five_quillas_competitions')->insert([['id' => 1, 'tournament_id' => 21], ['id' => 2, 'tournament_id' => 22]]);
    DB::table('five_quillas_phases')->insert([['id' => 1, 'competition_id' => 1], ['id' => 2, 'competition_id' => 2]]);
    DB::table('five_quillas_matches')->insert([['id' => 1, 'phase_id' => 1], ['id' => 2, 'phase_id' => 2]]);

    $operator = Livewire::test(CompetitionOrganizationUpdates::class, [
        'tournamentId' => 21,
        'revision' => CompetitionOrganizationUpdates::revisionFor(21),
        'viewVersion' => CompetitionViewVersion::current(organization: true),
    ]);

    Cache::put(CompetitionOrganizationUpdates::revisionKey(22), 'other-tournament');
    DB::table('five_quillas_matches')->where('id', 2)->update(['status' => 'completed']);
    $operator->call('checkForChanges')->assertNotDispatched('competition-organization-changed');

    DB::table('five_quillas_matches')->where('id', 1)->update([
        'status' => 'in_progress',
        'set_scores' => json_encode(['sets' => [['player_one_points' => 60, 'player_two_points' => 40]]]),
    ]);
    $operator->call('checkForChanges')
        ->assertDispatched('competition-organization-changed')
        ->assertSet('revision', CompetitionOrganizationUpdates::revisionFor(21));
    $operator->call('checkForChanges')->assertNotDispatched('competition-organization-changed');

    DB::table('five_quillas_matches')->where('id', 1)->update([
        'set_scores' => json_encode(['sets' => [['player_one_points' => 60, 'player_two_points' => 45]]]),
    ]);
    $operator->call('checkForChanges')->assertDispatched('competition-organization-changed');
});

it('signals an organization layout change even when the results did not change', function () {
    $operator = Livewire::test(CompetitionOrganizationUpdates::class, [
        'tournamentId' => 21,
        'revision' => CompetitionOrganizationUpdates::revisionFor(21),
        'viewVersion' => 'previous-layout',
    ]);

    $operator->call('checkForChanges')
        ->assertDispatched('competition-organization-view-changed', version: CompetitionViewVersion::current(organization: true))
        ->assertNotDispatched('competition-organization-changed');
});

it('refreshes a tab opened before layout versioning through its existing event', function () {
    $operator = Livewire::test(CompetitionOrganizationUpdates::class, [
        'tournamentId' => 21,
        'revision' => hash('sha256', serialize([[], null])),
    ]);

    $operator->call('checkForChanges')
        ->assertDispatched('competition-organization-changed')
        ->assertNotDispatched('competition-organization-view-changed');
    $operator->call('checkForChanges')->assertNotDispatched('competition-organization-changed');

    Cache::put(CompetitionOrganizationUpdates::revisionKey(21), 'refresh-from-support');
    $operator->call('checkForChanges')->assertDispatched('competition-organization-changed');
});
