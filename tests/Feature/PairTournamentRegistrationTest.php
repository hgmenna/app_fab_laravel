<?php

use App\Models\TournamentRegistration;
use App\Models\TournamentSlot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    foreach (['tournament_registration_participants', 'tournament_registrations', 'tournament_slots', 'tournament_modalities', 'discipline_modalities', 'tournaments', 'tournament_types', 'general_rankings', 'players', 'categories'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('categories', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('code');
    });
    Schema::create('players', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('category_id');
        $table->string('first_name');
        $table->string('last_name');
        $table->boolean('is_enabled_to_compete')->default(true);
    });
    Schema::create('general_rankings', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('player_id');
        $table->string('category');
    });
    Schema::create('tournament_types', fn (Blueprint $table) => $table->id());
    Schema::create('tournaments', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('tournament_type_id');
        $table->json('categories');
    });
    Schema::create('discipline_modalities', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->unsignedSmallInteger('players_per_registration')->default(1);
    });
    Schema::create('tournament_modalities', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('tournament_id');
        $table->foreignId('discipline_modality_id');
        $table->json('categories');
        $table->timestamps();
    });
    Schema::create('tournament_slots', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('tournament_id');
        $table->foreignId('tournament_modality_id');
        $table->unsignedInteger('max_players')->nullable();
        $table->timestamps();
    });
    Schema::create('tournament_registrations', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('tournament_id');
        $table->foreignId('tournament_modality_id');
        $table->foreignId('tournament_slot_id')->nullable();
        $table->foreignId('player_id');
        $table->foreignId('partner_player_id')->nullable();
        $table->json('player_ids')->nullable();
        $table->string('status')->default('pendiente');
        $table->decimal('points', 8, 2)->nullable();
        $table->timestamps();
    });
    Schema::create('tournament_registration_participants', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('tournament_registration_id');
        $table->foreignId('player_id');
        $table->unsignedSmallInteger('position');
        $table->timestamps();
    });

    DB::table('categories')->insert(['id' => 1, 'name' => 'Tercera', 'code' => 'T']);
    DB::table('players')->insert([
        ['id' => 1, 'category_id' => 1, 'first_name' => 'Ana', 'last_name' => 'Uno', 'is_enabled_to_compete' => true],
        ['id' => 2, 'category_id' => 1, 'first_name' => 'Beto', 'last_name' => 'Dos', 'is_enabled_to_compete' => true],
        ['id' => 3, 'category_id' => 1, 'first_name' => 'Carla', 'last_name' => 'Tres', 'is_enabled_to_compete' => true],
    ]);
    DB::table('tournament_types')->insert(['id' => 1]);
    DB::table('tournaments')->insert(['id' => 1, 'tournament_type_id' => 1, 'categories' => json_encode([1])]);
    DB::table('discipline_modalities')->insert([
        ['id' => 1, 'name' => 'Parejas', 'players_per_registration' => 2],
        ['id' => 2, 'name' => 'Bola 9', 'players_per_registration' => 1],
    ]);
    DB::table('tournament_modalities')->insert([
        ['id' => 1, 'tournament_id' => 1, 'discipline_modality_id' => 1, 'categories' => json_encode([1]), 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'tournament_id' => 1, 'discipline_modality_id' => 2, 'categories' => json_encode([1]), 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('tournament_slots')->insert(['id' => 1, 'tournament_id' => 1, 'tournament_modality_id' => 1, 'max_players' => 4, 'created_at' => now(), 'updated_at' => now()]);
});

it('requires the exact number of participants and occupies one registration', function () {
    expect(fn () => TournamentRegistration::query()->create([
        'tournament_id' => 1,
        'tournament_modality_id' => 1,
        'tournament_slot_id' => 1,
        'player_ids' => [1],
    ]))->toThrow(ValidationException::class);

    $registration = TournamentRegistration::query()->create([
        'tournament_id' => 1,
        'tournament_modality_id' => 1,
        'tournament_slot_id' => 1,
        'player_ids' => [1, 2],
        'points' => 25,
    ]);

    expect(TournamentSlot::query()->findOrFail(1)->occupiedPlaces())->toBe(1)
        ->and($registration->fresh('participants')->participantIds())->toBe([1, 2]);
});

it('prevents duplicates in one modality and permits an independent registration in another', function () {
    TournamentRegistration::query()->create(['tournament_id' => 1, 'tournament_modality_id' => 1, 'player_ids' => [1, 2]]);

    expect(fn () => TournamentRegistration::query()->create([
        'tournament_id' => 1,
        'tournament_modality_id' => 1,
        'player_ids' => [3, 2],
    ]))->toThrow(ValidationException::class);

    $otherModality = TournamentRegistration::query()->create([
        'tournament_id' => 1,
        'tournament_modality_id' => 2,
        'player_ids' => [2],
    ]);

    expect($otherModality->fresh('participants')->participantIds())->toBe([2]);
});
