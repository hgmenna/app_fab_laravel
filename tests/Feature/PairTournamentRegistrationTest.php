<?php

use App\Models\TournamentRegistration;
use App\Models\TournamentSlot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Schema::dropIfExists('tournament_registrations');
    Schema::dropIfExists('tournament_slots');
    Schema::dropIfExists('tournaments');
    Schema::dropIfExists('tournament_types');
    Schema::dropIfExists('players');
    Schema::dropIfExists('categories');

    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code');
    });

    Schema::create('players', function (Blueprint $table) {
        $table->id();
        $table->foreignId('category_id');
        $table->string('first_name');
        $table->string('last_name');
        $table->boolean('is_enabled_to_compete')->default(true);
    });

    Schema::create('tournament_types', function (Blueprint $table) {
        $table->id();
        $table->string('participation_mode');
    });

    Schema::create('tournaments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tournament_type_id');
        $table->json('categories');
    });

    Schema::create('tournament_slots', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tournament_id');
        $table->unsignedInteger('max_players')->nullable();
    });

    Schema::create('tournament_registrations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tournament_id');
        $table->foreignId('tournament_slot_id')->nullable();
        $table->foreignId('player_id');
        $table->foreignId('partner_player_id')->nullable();
        $table->string('status')->default('pendiente');
        $table->decimal('points', 8, 2)->nullable();
        $table->timestamps();
    });

    DB::table('categories')->insert([
        'id' => 1,
        'name' => 'Tercera',
        'code' => 'T',
    ]);

    DB::table('players')->insert([
        ['id' => 1, 'category_id' => 1, 'first_name' => 'Ana', 'last_name' => 'Uno', 'is_enabled_to_compete' => true],
        ['id' => 2, 'category_id' => 1, 'first_name' => 'Beto', 'last_name' => 'Dos', 'is_enabled_to_compete' => true],
        ['id' => 3, 'category_id' => 1, 'first_name' => 'Carla', 'last_name' => 'Tres', 'is_enabled_to_compete' => true],
    ]);

    DB::table('tournament_types')->insert([
        'id' => 1,
        'participation_mode' => 'pairs',
    ]);

    DB::table('tournaments')->insert([
        'id' => 1,
        'tournament_type_id' => 1,
        'categories' => json_encode([1]),
    ]);

    DB::table('tournament_slots')->insert([
        'id' => 1,
        'tournament_id' => 1,
        'max_players' => 4,
    ]);
});

afterEach(function () {
    Schema::dropIfExists('tournament_registrations');
    Schema::dropIfExists('tournament_slots');
    Schema::dropIfExists('tournaments');
    Schema::dropIfExists('tournament_types');
    Schema::dropIfExists('players');
    Schema::dropIfExists('categories');
});

it('requires two different players and occupies one team registration', function () {
    expect(fn () => TournamentRegistration::query()->create([
        'tournament_id' => 1,
        'tournament_slot_id' => 1,
        'player_id' => 1,
    ]))->toThrow(ValidationException::class);

    $registration = TournamentRegistration::query()->create([
        'tournament_id' => 1,
        'tournament_slot_id' => 1,
        'player_id' => 1,
        'partner_player_id' => 2,
        'points' => 25,
    ]);

    expect(TournamentSlot::query()->findOrFail(1)->occupiedPlaces())->toBe(1);
    expect($registration->player->registrations()->sum('points'))->toEqual(25);
    expect($registration->partner->partnerRegistrations()->sum('points'))->toEqual(25);

    $registration->delete();

    expect(TournamentRegistration::query()->whereKey($registration->id)->exists())->toBeFalse();
});

it('prevents either member of a pair from registering again', function () {
    TournamentRegistration::query()->create([
        'tournament_id' => 1,
        'tournament_slot_id' => 1,
        'player_id' => 1,
        'partner_player_id' => 2,
    ]);

    expect(fn () => TournamentRegistration::query()->create([
        'tournament_id' => 1,
        'tournament_slot_id' => 1,
        'player_id' => 3,
        'partner_player_id' => 2,
    ]))->toThrow(ValidationException::class);
});
