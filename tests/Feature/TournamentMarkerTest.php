<?php

use App\Models\Category;
use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasEntry;
use App\Models\FiveQuillasMatch;
use App\Models\FiveQuillasPhase;
use App\Models\MarkerTournamentLink;
use App\Models\Player;
use App\Models\Tournament;
use App\Services\FiveQuillasCompetitionService;
use App\Services\MarkerApi;
use App\Services\TournamentMarkerService;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['database.default' => 'marker_test', 'database.connections.marker_test' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => false,
    ]]);
    Schema::create('tournaments', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tournament_type_id')->nullable();
    });
    Schema::create('categories', function (Blueprint $table): void {
        $table->id();
        $table->string('code');
        $table->string('name');
        $table->timestamps();
    });
    Schema::create('players', function (Blueprint $table): void {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->unsignedBigInteger('category_id');
        $table->timestamps();
    });
    foreach ([
        '2026_10_17_120000_create_five_quillas_competition_tables.php',
        '2026_10_17_190000_add_computed_scores_to_five_quillas_matches.php',
        '2026_11_09_000000_create_marker_tournament_links.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
});

afterEach(fn () => DB::purge('marker_test'));

it('limita el puente temporal a un torneo y envía la clave de prueba', function () {
    config(['marker.url' => 'https://piloto.trycloudflare.com']);
    $api = app(MarkerApi::class);
    expect($api->configured())->toBeFalse();
    config(['marker.pilot_key' => str_repeat('a', 64), 'marker.pilot_tournament_id' => 42]);
    expect($api->availableFor(42))->toBeTrue()->and($api->availableFor(43))->toBeFalse();

    Http::fake(['https://piloto.trycloudflare.com/api/v1/fab/health' => Http::response(['ok' => true])]);
    expect($api->request('GET', '/health'))->toBe(['ok' => true]);
    Http::assertSent(fn (Request $request) => $request->hasHeader('X-Marker-Pilot-Key', str_repeat('a', 64)));
});

it('envía formato y categorías, recibe el tanteador y registra el resultado confirmado', function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'marker.url' => 'https://marcador.test']);
    $tournament = Tournament::query()->findOrFail(DB::table('tournaments')->insertGetId(['tournament_type_id' => null]));
    $competition = FiveQuillasCompetition::create(['tournament_id' => $tournament->id, 'public_slug' => 'prueba-marcador']);
    $phase = FiveQuillasPhase::create([
        'competition_id' => $competition->id, 'code' => 'final', 'name' => 'Final',
        'phase_type' => 'bracket', 'sort_order' => 1, 'sets_to_play' => 3, 'points_per_set' => 60,
    ]);
    $entries = collect(['1', 'M'])->map(function (string $code) use ($competition) {
        $category = Category::create(['name' => 'Categoría '.$code, 'code' => $code]);
        $player = Player::create(['first_name' => 'Jugador', 'last_name' => $code, 'category_id' => $category->id]);

        return FiveQuillasEntry::create(['competition_id' => $competition->id, 'player_id' => $player->id, 'status' => 'eligible']);
    });
    $match = FiveQuillasMatch::create([
        'phase_id' => $phase->id, 'round_number' => 1, 'bracket_order' => 1, 'label' => 'Final',
        'player_one_entry_id' => $entries[0]->id, 'player_two_entry_id' => $entries[1]->id,
        'table_number' => 2, 'status' => 'called',
    ]);
    $other = FiveQuillasMatch::create([
        'phase_id' => $phase->id, 'round_number' => 1, 'bracket_order' => 2,
        'player_one_entry_id' => $entries[0]->id, 'player_two_entry_id' => $entries[1]->id,
    ]);
    $remote = [];
    Http::fake(function (Request $request) use (&$remote) {
        if (str_ends_with($request->url(), '/login')) {
            return Http::response(['code' => 'club-demo', 'club' => 'Club Demo', 'token' => str_repeat('a', 64), 'expiresAt' => now()->addWeek()->valueOf()]);
        }
        if (str_ends_with($request->url(), '/tables')) {
            return Http::response(['tables' => [
                ['number' => 1, 'linked' => false, 'available' => false],
                ['number' => 2, 'linked' => true, 'available' => true],
                ['number' => 3, 'linked' => true, 'available' => false],
                ['number' => 4, 'linked' => true, 'available' => true],
            ]]);
        }
        if ($request->method() === 'POST') {
            return Http::response(['id' => $request['id']]);
        }

        return Http::response(['matches' => [$remote]]);
    });
    $service = app(TournamentMarkerService::class);
    $service->connect($tournament->id, ['institution' => 'club-demo', 'username' => 'control_mesas', 'password' => 'contraseña-demo']);
    $link = MarkerTournamentLink::query()->where('tournament_id', $tournament->id)->firstOrFail();
    expect($link->getRawOriginal('token'))->not->toBe($link->token);
    $numbers = $service->tableNumbers($link);
    expect($numbers)->toBe(['linked' => [2, 3, 4], 'available' => [2, 4]]);
    $organization = app(FiveQuillasCompetitionService::class);
    $other->refresh();
    expect($organization->availableTableOptions($other))->toBe([])
        ->and($organization->availableTableOptions($other, $numbers['available']))->toBe([4 => 'Mesa 4']);
    expect(fn () => $organization->callMatch($other, 3, $other->editRevision(), $numbers['available']))->toThrow(ValidationException::class);
    expect((int) $organization->callMatch($other, 4, $other->editRevision(), $numbers['available'])->table_number)->toBe(4);
    $schedulePhase = FiveQuillasPhase::create([
        'competition_id' => $competition->id, 'code' => 'play-in', 'name' => 'Emparejamientos',
        'phase_type' => 'play_in', 'sort_order' => 2,
        'settings' => ['schedules' => [['starts_at' => '2026-11-10 10:00', 'tables' => 9, 'estimated_minutes' => 25]]],
    ]);
    foreach (range(1, 4) as $position) {
        FiveQuillasMatch::create(['phase_id' => $schedulePhase->id, 'bracket_order' => $position]);
    }
    expect($organization->scheduleConfiguredMatches($schedulePhase, 0, markerCapacity: count($numbers['linked'])))->toBe(4)
        ->and($schedulePhase->matches()->where('bracket_order', 4)->first()->scheduled_at->format('H:i'))->toBe('10:25')
        ->and($schedulePhase->fresh()->settings['schedules'][0]['tables'])->toBe(9);
    $service->send($link, $match);
    $assignment = $link->assignments()->firstOrFail();
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/matches')
        && $request['pointsPerSet'] === 60 && $request['setsToWin'] === 2 && $request['table'] === 2
        && array_column($request['players'], 'category') === ['1', 'M']);

    $remote = ['id' => $assignment->external_id, 'status' => 'in_progress', 'points' => [30, 45], 'setsWon' => [0, 1]];
    expect($service->live($link, [$assignment])[$assignment->id]['points'])->toBe([30, 45])
        ->and($match->fresh()->status)->toBe('called');
    $remote['status'] = 'completed';
    $remote['sets'] = [[25, 60], [60, 30], [56, 63]];
    $service->live($link, [$assignment]);
    expect($match->fresh()->status)->toBe('completed')
        ->and((int) $match->fresh()->winner_entry_id)->toBe($entries[1]->id)
        ->and($match->fresh()->set_scores['sets'][2])->toBe(['player_one_points' => 56, 'player_two_points' => 60])
        ->and($assignment->fresh()->status)->toBe('imported');
});
