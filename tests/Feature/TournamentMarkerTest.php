<?php

use App\Filament\Resources\Tournaments\Pages\ManageTournamentCompetition;
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
use Illuminate\Support\Facades\Cache;
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
        '2026_11_09_010000_add_marker_username_to_tournament_links.php',
    ] as $migration) {
        (require database_path('migrations/'.$migration))->up();
    }
});

afterEach(fn () => DB::purge('marker_test'));

it('habilita y detiene el piloto desde Artisan sin cambiar la configuración de entorno', function () {
    config(['app.key' => 'base64:'.base64_encode(str_repeat('a', 32)), 'marker.url' => null]);
    $tournament = DB::table('tournaments')->insertGetId(['tournament_type_id' => null]);
    $key = str_repeat('b', 64);
    $url = 'https://piloto.trycloudflare.com';
    Http::fake([$url.'/api/v1/fab/health' => Http::response(['ok' => true])]);

    $this->artisan('marcador:piloto', ['torneo' => $tournament, 'url' => $url])
        ->expectsQuestion('Clave temporal del puente', $key)
        ->assertSuccessful();
    expect(config('marker.url'))->toBeNull()
        ->and(Cache::get('marker:temporary-pilot')['key'])->not->toBe($key)
        ->and(app(MarkerApi::class)->availableFor($tournament))->toBeTrue()
        ->and(app(MarkerApi::class)->availableFor($tournament + 1))->toBeFalse();
    Http::assertSent(fn (Request $request) => $request->hasHeader('X-Marker-Pilot-Key', $key));

    $this->travel(4)->hours();
    expect(app(MarkerApi::class)->availableFor($tournament))->toBeFalse();
    $this->travelBack();
    $this->artisan('marcador:piloto', ['--detener' => true])->assertSuccessful();
    expect(Cache::get('marker:temporary-pilot'))->toBeNull();
});

it('muestra la vinculación de marcadores directamente en el entorno local', function () {
    config(['marker.url' => null]);
    $page = new ManageTournamentCompetition;
    $page->record = Tournament::query()->findOrFail(DB::table('tournaments')->insertGetId(['tournament_type_id' => null]));
    expect($page->connectMarkersAction()->isVisible())->toBeFalse();

    $this->app->detectEnvironment(fn () => 'local');
    expect($page->connectMarkersAction()->isVisible())->toBeTrue();
    Http::fake(['http://127.0.0.1:8792/api/v1/fab/login' => Http::response(['code' => 'club-demo'])]);

    $credentials = ['institution' => 'club-demo', 'username' => 'control_mesas', 'password' => 'control-demo'];
    expect(app(MarkerApi::class)->request('POST', '/login', $credentials))->toBe(['code' => 'club-demo']);
    Http::assertSent(fn (Request $request) => $request->data() === $credentials && ! $request->hasHeader('X-Marker-Pilot-Key'));
});

it('limita el puente temporal a un torneo y envía la clave de prueba', function () {
    config(['marker.url' => 'https://piloto.trycloudflare.com']);
    $api = app(MarkerApi::class);
    expect($api->configured())->toBeFalse();
    config(['marker.pilot_key' => str_repeat('a', 64), 'marker.pilot_tournament_id' => 42]);
    expect($api->availableFor(42))->toBeTrue()->and($api->availableFor(43))->toBeFalse();
    expect($api->pilotClubOptions())->toBe(['club-demo' => 'Club Demo', 'club-sur' => 'Club Sur']);
    config(['marker.pilot_clubs' => 'club-demo, Mi-Sala, , invalido!']);
    expect($api->pilotClubOptions())->toBe(['club-demo' => 'Club Demo', 'mi-sala' => 'Mi Sala']);
    expect(fn () => app(TournamentMarkerService::class)->connect(42, ['institution' => 'club-sur']))
        ->toThrow(RuntimeException::class, 'Elegí un club habilitado para la prueba.');

    Http::fake([
        'https://piloto.trycloudflare.com/api/v1/fab/health' => Http::response(['ok' => true]),
        'https://piloto.trycloudflare.com/api/v1/fab/users?club=club-demo' => Http::response(['users' => ['administrador', 'control_mesas']]),
    ]);
    expect(app(TournamentMarkerService::class)->pilotUsers('club-demo'))->toBe(['administrador' => 'administrador', 'control_mesas' => 'control_mesas']);
    expect(app(TournamentMarkerService::class)->pilotUsers('club-sur'))->toBe([]);
    expect($api->request('GET', '/health'))->toBe(['ok' => true]);
    Http::assertSent(fn (Request $request) => $request->hasHeader('X-Marker-Pilot-Key', str_repeat('a', 64)));
});

it('usa el marcador de producción de FAB sin configurar una URL ni enviar la clave del puente', function () {
    config(['marker.url' => null, 'marker.pilot_key' => null, 'marker.pilot_tournament_id' => null]);
    $this->app->detectEnvironment(fn () => 'production');
    $url = 'https://marcador.federacionargentinadebillar.org/api/v1/fab/login';
    Http::fake([$url => Http::response(['code' => 'club-prueba'])]);
    $api = app(MarkerApi::class);
    expect($api->availableFor(42))->toBeTrue()
        ->and($api->pilotClubOptions())->toBeNull()
        ->and($api->request('POST', '/login', ['institution' => 'club-prueba']))->toBe(['code' => 'club-prueba']);
    Http::assertSent(fn (Request $request) => $request->url() === $url && ! $request->hasHeader('X-Marker-Pilot-Key'));

    $this->app->detectEnvironment(fn () => 'local');
    config(['app.url' => 'https://sistem.federacionargentinadebillar.org']);
    expect($api->request('POST', '/login', ['institution' => 'club-prueba']))->toBe(['code' => 'club-prueba']);
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
    ]);
    $other = FiveQuillasMatch::create([
        'phase_id' => $phase->id, 'round_number' => 1, 'bracket_order' => 2,
        'player_one_entry_id' => $entries[0]->id, 'player_two_entry_id' => $entries[1]->id,
    ]);
    $remote = [];
    $failedTableFourId = null;
    Http::fake(function (Request $request) use (&$remote, &$failedTableFourId) {
        if (str_ends_with($request->url(), '/login')) {
            return Http::response(['code' => 'club-demo', 'club' => 'Club Demo', 'username' => 'control_mesas', 'token' => str_repeat('a', 64), 'expiresAt' => now()->addWeek()->valueOf()]);
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
            if ($request['table'] === 4 && $failedTableFourId === null) {
                $failedTableFourId = $request['id'];

                return Http::response([], 503);
            }

            return Http::response(['id' => $request['id']]);
        }

        return Http::response(['matches' => [$remote]]);
    });
    $service = app(TournamentMarkerService::class);
    $service->connect($tournament->id, ['institution' => 'club-demo', 'username' => 'control_mesas', 'password' => 'contraseña-demo']);
    $link = MarkerTournamentLink::query()->where('tournament_id', $tournament->id)->firstOrFail();
    expect($link->getRawOriginal('token'))->not->toBe($link->token)
        ->and($link->marker_username)->toBe('control_mesas');
    $numbers = $service->tableNumbers($link);
    expect($numbers)->toBe(['linked' => [2, 3, 4], 'available' => [2, 4], 'unlinked' => [1]]);
    $organization = app(FiveQuillasCompetitionService::class);
    $service->callAndSend($link, $match, 2, $match->refresh()->editRevision());
    expect($match->fresh()->status)->toBe('called');
    expect(fn () => $organization->configureFormat($competition, [[
        'id' => $phase->id, 'name' => $phase->name, 'phase_type' => 'bracket',
        'seeding_method' => 'manual', 'sets_to_play' => 5, 'points_per_set' => 60, 'is_enabled' => true,
    ]]))->toThrow(ValidationException::class, 'ya se aplicaron a partidos llamados');
    expect((int) $phase->fresh()->sets_to_play)->toBe(3);
    $other->refresh();
    expect($organization->availableTableOptions($other))->toBe([])
        ->and($organization->availableTableOptions($other, $numbers['available']))->toBe([4 => 'Mesa 4']);
    expect(fn () => $organization->callMatch($other, 3, $other->editRevision(), $numbers['available']))->toThrow(ValidationException::class);
    expect(fn () => $service->callAndSend($link, $other, 4, $other->editRevision()))->toThrow(RuntimeException::class, 'el envío está pendiente');
    $pending = $service->outstanding($link, $other);
    expect((int) $other->fresh()->table_number)->toBe(4)
        ->and($pending->status)->toBe('pending')
        ->and($pending->external_id)->toBe($failedTableFourId);
    $service->send($link, $other);
    expect($pending->fresh()->status)->toBe('sent')
        ->and($pending->fresh()->external_id)->toBe($failedTableFourId);
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
    $assignment = $service->outstanding($link, $match);
    expect($assignment->status)->toBe('sent');
    Http::assertSent(fn (Request $request) => $request->method() === 'POST'
        && str_ends_with($request->url(), '/matches')
        && $request['pointsPerSet'] === 60 && $request['setsToWin'] === 2 && $request['table'] === 2
        && array_column($request['players'], 'category') === ['1', 'M']);

    $remote = ['id' => $assignment->external_id, 'status' => 'in_progress', 'points' => [30, 45], 'setsWon' => [0, 1]];
    expect($service->live($link, [$assignment])[$assignment->id]['points'])->toBe([30, 45])
        ->and(Cache::get(TournamentMarkerService::publicScoreKey($match->id))['points'])->toBe([30, 45])
        ->and($match->fresh()->status)->toBe('called');
    $remote['status'] = 'completed';
    $remote['sets'] = [[25, 60], [60, 30], [56, 63]];
    $service->live($link, [$assignment]);
    expect($match->fresh()->status)->toBe('completed')
        ->and((int) $match->fresh()->winner_entry_id)->toBe($entries[1]->id)
        ->and($match->fresh()->set_scores['sets'][2])->toBe(['player_one_points' => 56, 'player_two_points' => 60])
        ->and($assignment->fresh()->status)->toBe('imported')
        ->and(Cache::get(TournamentMarkerService::publicScoreKey($match->id)))->toBeNull();
});
