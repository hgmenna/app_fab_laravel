<?php

use App\Models\Category;
use App\Models\City;
use App\Models\Club;
use App\Models\Country;
use App\Models\Discipline;
use App\Models\State;
use App\Models\Tournament;
use App\Models\TournamentRegulationSetting;
use App\Models\TournamentType;
use App\Services\TournamentRegulationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (['tournaments', 'tournament_regulation_settings', 'tournament_types', 'categories', 'clubs', 'cities', 'states', 'countries', 'disciplines'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('countries', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('iso2');
        $table->string('iso3');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('states', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('country_id');
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('cities', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('country_id');
        $table->unsignedBigInteger('state_id');
        $table->string('name');
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('clubs', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('address')->nullable();
        $table->unsignedBigInteger('city_id');
        $table->timestamps();
    });
    Schema::create('disciplines', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->nullable();
        $table->boolean('active')->default(true);
        $table->string('affiliation_mode')->default('provincial');
        $table->unsignedBigInteger('direct_federation_id')->nullable();
        $table->timestamps();
    });
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('discipline_id')->nullable();
        $table->string('name');
        $table->string('code')->nullable();
        $table->unsignedInteger('order')->default(0);
        $table->timestamps();
    });
    Schema::create('tournament_types', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('discipline_id');
        $table->string('name');
        $table->string('code')->nullable();
        $table->string('participation_mode')->default('individual');
        $table->boolean('is_official')->default(false);
        $table->boolean('exclusive_during_dates')->default(false);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
    Schema::create('tournament_regulation_settings', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('discipline_id');
        $table->boolean('enabled')->default(true);
        $table->decimal('minimum_distance_km', 8, 2)->nullable();
        $table->boolean('check_non_official_distance')->default(true);
        $table->boolean('block_non_official_against_official_same_state')->default(true);
        $table->date('effective_from')->nullable();
        $table->date('effective_until')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });
    Schema::create('tournaments', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->unsignedBigInteger('discipline_id');
        $table->unsignedBigInteger('tournament_type_id');
        $table->date('start_date');
        $table->date('end_date')->nullable();
        $table->string('status')->default('draft');
        $table->unsignedBigInteger('venue_id');
        $table->json('categories');
        $table->json('manual_route_checks')->nullable();
        $table->timestamps();
    });

    $country = Country::query()->forceCreate([
        'name' => 'Argentina', 'iso2' => 'AR', 'iso3' => 'ARG', 'is_active' => true,
    ]);
    $this->state = State::query()->create([
        'country_id' => $country->id, 'name' => 'Santa Fe', 'is_active' => true,
    ]);
    $city = City::query()->create([
        'country_id' => $country->id, 'state_id' => $this->state->id,
        'name' => 'Rosario', 'is_active' => true,
    ]);
    $this->clubA = Club::query()->create(['name' => 'Club A', 'address' => 'Calle 1 100', 'city_id' => $city->id]);
    $this->clubB = Club::query()->create(['name' => 'Club B', 'address' => 'Calle 2 200', 'city_id' => $city->id]);
    $this->discipline = Discipline::query()->create(['name' => '5 Quillas', 'code' => 'five_quillas', 'active' => true]);
    $this->category = Category::query()->create([
        'discipline_id' => $this->discipline->id, 'name' => 'Primera', 'code' => 'P', 'order' => 1,
    ]);
    $this->officialType = TournamentType::query()->create([
        'discipline_id' => $this->discipline->id, 'name' => 'Oficial', 'code' => 'OF',
        'participation_mode' => 'individual', 'is_official' => true, 'is_active' => true,
    ]);
    $this->nonOfficialType = TournamentType::query()->create([
        'discipline_id' => $this->discipline->id, 'name' => 'Amistad', 'code' => 'AM',
        'participation_mode' => 'individual', 'is_official' => false, 'is_active' => true,
    ]);
    TournamentRegulationSetting::query()->create([
        'discipline_id' => $this->discipline->id,
        'minimum_distance_km' => 150,
        'enabled' => true,
        'check_non_official_distance' => true,
        'block_non_official_against_official_same_state' => true,
    ]);
});

afterEach(function () {
    foreach (['tournaments', 'tournament_regulation_settings', 'tournament_types', 'categories', 'clubs', 'cities', 'states', 'countries', 'disciplines'] as $table) {
        Schema::dropIfExists($table);
    }
});

function regulationTournament(array $attributes): Tournament
{
    return Tournament::query()->create(array_merge([
        'name' => 'Existente',
        'discipline_id' => test()->discipline->id,
        'tournament_type_id' => test()->nonOfficialType->id,
        'start_date' => '2026-10-10',
        'end_date' => '2026-10-12',
        'status' => 'published',
        'venue_id' => test()->clubA->id,
        'categories' => [test()->category->id],
    ], $attributes));
}

function regulationCandidate(array $attributes): Tournament
{
    return (new Tournament)->forceFill(array_merge([
        'name' => 'Nuevo',
        'discipline_id' => test()->discipline->id,
        'tournament_type_id' => test()->nonOfficialType->id,
        'start_date' => '2026-10-12',
        'end_date' => '2026-10-13',
        'status' => 'published',
        'venue_id' => test()->clubB->id,
        'categories' => [test()->category->id],
    ], $attributes));
}

it('blocks every overlapping tournament when either type is exclusive', function () {
    $this->officialType->update(['exclusive_during_dates' => true]);
    regulationTournament(['tournament_type_id' => $this->officialType->id]);

    $result = (new TournamentRegulationService)->evaluate(regulationCandidate([]));

    expect($result->passes())->toBeFalse()
        ->and($result->conflicts[0]['rule'])->toBe('exclusive_dates');
});

it('blocks an official and a non official tournament in the same province and category', function () {
    regulationTournament(['tournament_type_id' => $this->officialType->id]);

    $result = (new TournamentRegulationService)->evaluate(regulationCandidate([]));

    expect($result->passes())->toBeFalse()
        ->and($result->conflicts[0]['rule'])->toBe('official_same_state');
});

it('blocks nearby non official tournaments using the driving distance', function () {
    regulationTournament([]);
    $result = (new TournamentRegulationService)->evaluate(regulationCandidate([
        'manual_route_checks' => [[
            'conflicting_tournament_id' => 1,
            'distance_km' => 87.4,
            'evidence_path' => 'tournament-regulation-evidence/prueba.png',
            'checked_by' => 1,
            'checked_at' => now()->toIso8601String(),
        ]],
    ]));

    expect($result->passes())->toBeFalse()
        ->and($result->conflicts[0]['rule'])->toBe('minimum_distance')
        ->and($result->conflicts[0]['route']['distance_meters'])->toBe(87400);
});

it('requires a manual distance and evidence for every overlapping non official tournament', function () {
    regulationTournament([]);

    $result = (new TournamentRegulationService)->evaluate(regulationCandidate([]));

    expect($result->passes())->toBeFalse()
        ->and($result->conflicts[0]['rule'])->toBe('manual_distance_required')
        ->and($result->conflicts[0]['route']['google_maps_url'])->toContain('google.com/maps/dir');
});
