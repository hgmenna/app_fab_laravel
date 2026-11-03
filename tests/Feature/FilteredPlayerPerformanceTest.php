<?php

use App\Filament\Resources\Players\Pages\FilteredPlayerPerformance;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config(['app.key' => str_repeat('t', 32)]);
    $definitions = [
        'disciplines' => ['name' => 'string', 'affiliation_mode' => 'string', 'direct_federation_id' => 'integer'],
        'states' => ['federation_id' => 'integer'], 'cities' => ['state_id' => 'integer'], 'clubs' => ['city_id' => 'integer'],
        'categories' => ['name' => 'string', 'discipline_id' => 'integer'],
        'players' => ['first_name' => 'string', 'last_name' => 'string', 'category_id' => 'integer', 'discipline_id' => 'integer', 'club_id' => 'integer'],
        'tournament_types' => ['name' => 'string', 'assigns_points' => 'boolean'],
        'tournaments' => ['name' => 'string', 'discipline_id' => 'integer', 'tournament_type_id' => 'integer', 'end_date' => 'date'],
        'tournament_instances' => [],
        'tournament_registrations' => ['tournament_id' => 'integer', 'tournament_instance_id' => 'integer', 'result_code' => 'string', 'points' => 'decimal'],
        'tournament_registration_participants' => ['tournament_registration_id' => 'integer', 'player_id' => 'integer', 'position' => 'integer'],
    ];
    foreach ($definitions as $name => $columns) {
        Schema::create($name, function (Blueprint $table) use ($columns) {
            $table->id();
            foreach ($columns as $column => $type) $table->$type($column)->nullable();
        });
    }
    $this->reportTables = array_keys($definitions);
    DB::table('disciplines')->insert([
        ['id' => 1, 'name' => 'Provincial', 'affiliation_mode' => 'provincial', 'direct_federation_id' => null],
        ['id' => 2, 'name' => 'Directa', 'affiliation_mode' => 'direct', 'direct_federation_id' => 2],
    ]);
    DB::table('states')->insert([['id' => 1, 'federation_id' => 1], ['id' => 2, 'federation_id' => 2]]);
    DB::table('cities')->insert([['id' => 1, 'state_id' => 1], ['id' => 2, 'state_id' => 2]]);
    DB::table('clubs')->insert([['id' => 1, 'city_id' => 1], ['id' => 2, 'city_id' => 2]]);
    DB::table('categories')->insert([
        ['id' => 1, 'name' => 'Primera', 'discipline_id' => 1], ['id' => 2, 'name' => 'Segunda', 'discipline_id' => 1],
    ]);
    foreach ([['Alfa', 1, 1, 1], ['Beta', 2, 1, 1], ['Gamma', 1, 2, 1], ['Delta', 1, 1, 2]] as $i => [$name, $category, $discipline, $club]) {
        DB::table('players')->insert(['id' => $i + 1, 'first_name' => 'Jugador', 'last_name' => $name,
            'category_id' => $category, 'discipline_id' => $discipline, 'club_id' => $club]);
    }
    DB::table('tournament_types')->insert(['id' => 1, 'name' => 'Puntuable', 'assigns_points' => true]);
    DB::table('tournaments')->insert(['id' => 1, 'name' => 'Torneo', 'discipline_id' => 1, 'tournament_type_id' => 1, 'end_date' => '2025-01-01']);
    foreach ([10, 50, 30, 20] as $i => $points) {
        DB::table('tournament_registrations')->insert(['id' => $i + 1, 'tournament_id' => 1, 'points' => $points]);
        DB::table('tournament_registration_participants')->insert(['tournament_registration_id' => $i + 1, 'player_id' => $i + 1, 'position' => 1]);
    }
    $this->page = new FilteredPlayerPerformance;
    $this->page->playerIds = [1, 2, 3, 4];
});

afterEach(function () {
    foreach (array_reverse($this->reportTables) as $table) Schema::dropIfExists($table);
});

it('shares descending total points ordering between the page and PDF data', function () {
    DB::table('tournament_registrations')->insert(['id' => 5, 'tournament_id' => 1, 'points' => 45]);
    DB::table('tournament_registration_participants')->insert(['tournament_registration_id' => 5, 'player_id' => 1, 'position' => 1]);
    $report = $this->page->reportData();
    expect($report['players']->pluck('id')->all())->toBe([1, 2, 3, 4])
        ->and((float) $report['players']->first()['points'])->toBe(55.0)
        ->and((float) $report['totals']['points'])->toBe(155.0);
});

it('combines federation and category filters and respects direct affiliation', function () {
    $this->page->federationId = '1';
    expect($this->page->reportData()['players']->pluck('id')->all())->toBe([2, 1]);
    $this->page->categoryId = '1';
    expect($this->page->reportData()['players']->pluck('id')->all())->toBe([1]);
    $this->page->federationId = '2';
    expect($this->page->reportData()['players']->pluck('id')->all())->toBe([3, 4]);
    $this->page->playerIds = [1, 4];
    expect($this->page->reportData()['players']->pluck('id')->all())->toBe([4]);
});

it('clears the category when changing discipline', function () {
    $this->page->categoryId = '2'; $this->page->typeIds = [1];
    $this->page->updatedDisciplineId();
    expect($this->page->categoryId)->toBe('')->and($this->page->typeIds)->toBe([]);
});

it('prepares a direct download with frozen filters without dispatching background work', function () {
    \Illuminate\Support\Facades\Bus::fake([\App\Jobs\GeneratePlayerPerformancePdf::class]);
    $this->page->federationId = '2';
    $this->page->categoryId = '1';
    $this->page->startPdfExport();
    $token = $this->page->pdfExportToken;
    expect($token)->not->toBeNull();
    $filters = session('player-performance-pdf.'.$token.'.filters');
    expect($filters['federationId'])->toBe('2')->and($filters['categoryId'])->toBe('1')
        ->and($filters['playerIds'])->toBe([1, 2, 3, 4]);
    $this->page->startPdfExport();
    expect($this->page->pdfExportToken)->toBe($token);
    \Illuminate\Support\Facades\Bus::assertNothingDispatched();
    $this->page->finishPdfExport($token, true);
    expect($this->page->pdfExportToken)->toBeNull();
});

it('generates a private PDF using the same filtered descending points data', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $token = (string) \Illuminate\Support\Str::uuid();
    \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()
        ->withArgs(fn ($view, $data) => $view === 'pdf.filtered-player-performance'
            && $data['players']->pluck('id')->all() === [3, 4])
        ->andReturnSelf();
    \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('setPaper')->with('a4', 'landscape')->once()->andReturnSelf();
    \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('output')->once()->andReturn('%PDF-1.4 test');
    (new \App\Jobs\GeneratePlayerPerformancePdf($token, ['playerIds' => [1, 2, 3, 4], 'federationId' => '2']))->handle();
    \Illuminate\Support\Facades\Storage::disk('local')->assertExists('performance-exports/'.$token.'.pdf');
    $this->withSession(['player-performance-pdf.'.$token => ['user_id' => null, 'expires_at' => now()->addHour()->timestamp]])
        ->get(route('players.performance-pdf', ['token' => $token]))->assertOk()->assertDownload();
});

it('does not expose PDFs outside their owning session or after expiry', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $token = (string) \Illuminate\Support\Str::uuid();
    \Illuminate\Support\Facades\Storage::disk('local')->put('performance-exports/'.$token.'.pdf', '%PDF-1.4 test');
    \Illuminate\Support\Facades\Cache::put('player-performance-pdf:'.$token, 'ready');
    $url = route('players.performance-pdf', ['token' => $token]);
    $this->get($url)->assertNotFound();
    $this->withSession(['player-performance-pdf.'.$token => ['user_id' => 999, 'expires_at' => now()->addHour()->timestamp]])
        ->get($url)->assertNotFound();
    $this->withSession(['player-performance-pdf.'.$token => ['user_id' => null, 'expires_at' => now()->subHour()->timestamp]])
        ->get($url)->assertNotFound();
});

it('generates and returns a real PDF through the download route without a pre-existing file', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $token = (string) \Illuminate\Support\Str::uuid();
    $this->withSession(['player-performance-pdf.'.$token => [
        'user_id' => null, 'expires_at' => now()->addHour()->timestamp, 'filters' => ['playerIds' => [1, 2, 3, 4]],
    ]])->get(route('players.performance-pdf', ['token' => $token]))->assertOk()->assertDownload()
        ->assertHeader('Content-Type', 'application/pdf');
    expect(\Illuminate\Support\Facades\Storage::disk('local')->get('performance-exports/'.$token.'.pdf'))->toStartWith('%PDF-');
});

it('exports a real 56-player 133-participation report without background processing', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    for ($id = 5; $id <= 56; $id++) {
        DB::table('players')->insert(['id' => $id, 'first_name' => 'José', 'last_name' => 'González '.$id,
            'category_id' => 1, 'discipline_id' => 1, 'club_id' => 1]);
    }
    for ($id = 5; $id <= 133; $id++) {
        DB::table('tournament_registrations')->insert(['id' => $id, 'tournament_id' => 1, 'points' => $id % 20]);
        DB::table('tournament_registration_participants')->insert(['tournament_registration_id' => $id,
            'player_id' => (($id - 5) % 56) + 1, 'position' => 1]);
    }
    $token = (string) \Illuminate\Support\Str::uuid();
    $this->withSession(['player-performance-pdf.'.$token => [
        'user_id' => null, 'expires_at' => now()->addHour()->timestamp, 'filters' => ['playerIds' => range(1, 56)],
    ]])->get(route('players.performance-pdf', ['token' => $token]))->assertOk()->assertDownload();
    expect(\Illuminate\Support\Facades\Storage::disk('local')->get('performance-exports/'.$token.'.pdf'))->toStartWith('%PDF-');
});
