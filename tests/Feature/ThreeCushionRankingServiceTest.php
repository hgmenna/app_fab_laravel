<?php

use App\Models\ThreeCushionRanking;
use App\Services\ThreeCushionRankingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (['three_cushion_rankings', 'three_cushion_stage_results', 'tournament_registration_participants', 'tournament_registrations', 'tournament_modalities', 'tournaments', 'tournament_types', 'players', 'clubs', 'categories', 'disciplines'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('disciplines', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('code');
        $table->timestamps();
    });
    Schema::create('categories', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('discipline_id');
        $table->string('name');
        $table->string('code');
        $table->integer('order')->default(0);
        $table->timestamps();
    });
    Schema::create('clubs', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
    Schema::create('players', function (Blueprint $table): void {
        $table->id();
        $table->string('first_name');
        $table->string('last_name');
        $table->foreignId('club_id')->nullable();
        $table->foreignId('category_id')->nullable();
        $table->foreignId('discipline_id')->nullable();
        $table->timestamps();
    });
    Schema::create('tournament_types', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('discipline_id');
        $table->string('name');
        $table->boolean('affects_ranking');
        $table->timestamps();
    });
    Schema::create('tournaments', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('discipline_id');
        $table->foreignId('tournament_type_id');
        $table->string('name');
        $table->date('end_date');
        $table->json('categories')->nullable();
        $table->timestamps();
    });
    Schema::create('tournament_modalities', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('tournament_id');
        $table->json('categories')->nullable();
        $table->timestamps();
    });
    Schema::create('tournament_registrations', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('tournament_id');
        $table->string('status');
        $table->decimal('points', 12, 2)->nullable();
        $table->timestamps();
    });
    Schema::create('tournament_registration_participants', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('tournament_registration_id');
        $table->foreignId('player_id');
        $table->unsignedInteger('position')->default(1);
        $table->timestamps();
    });
    Schema::create('three_cushion_stage_results', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('tournament_registration_id');
        $table->foreignId('player_id');
        $table->foreignId('category_id');
        $table->unsignedInteger('caroms');
        $table->unsignedInteger('innings');
        $table->decimal('general_average', 14, 6);
        $table->decimal('best_match_average', 14, 6);
        $table->unsignedInteger('high_run');
        $table->date('high_run_achieved_at')->nullable();
        $table->timestamps();
    });
    Schema::create('three_cushion_rankings', function (Blueprint $table): void {
        $table->id();
        $table->unsignedSmallInteger('season');
        $table->foreignId('category_id');
        $table->foreignId('player_id');
        $table->unsignedInteger('position');
        $table->unsignedInteger('total_caroms');
        $table->unsignedInteger('total_innings');
        $table->unsignedInteger('high_run');
        $table->date('high_run_achieved_at')->nullable();
        $table->decimal('general_average', 14, 6);
        $table->decimal('best_match_average', 14, 6);
        $table->decimal('ranking_points', 12, 2);
        $table->unsignedInteger('stages_played');
        $table->unsignedInteger('total_stages');
        $table->timestamps();
    });
});

test('calcula y ordena el ranking anual por categoría', function () {
    $disciplineId = DB::table('disciplines')->insertGetId(['name' => 'Carambola 3 Bandas', 'code' => 'carambola_3_bandas', 'created_at' => now(), 'updated_at' => now()]);
    $categoryId = DB::table('categories')->insertGetId(['discipline_id' => $disciplineId, 'name' => 'Primera', 'code' => 'P', 'created_at' => now(), 'updated_at' => now()]);
    $clubId = DB::table('clubs')->insertGetId(['name' => 'Club Central', 'created_at' => now(), 'updated_at' => now()]);
    $players = collect([['Ana', 'Alvarez'], ['Bruno', 'Benitez']])->map(fn (array $name): int => DB::table('players')->insertGetId(['first_name' => $name[0], 'last_name' => $name[1], 'club_id' => $clubId, 'category_id' => $categoryId, 'discipline_id' => $disciplineId, 'created_at' => now(), 'updated_at' => now()]));
    $typeId = DB::table('tournament_types')->insertGetId(['discipline_id' => $disciplineId, 'name' => 'Ranking', 'affects_ranking' => true, 'created_at' => now(), 'updated_at' => now()]);
    $tournaments = collect(['2026-03-10', '2026-06-10'])->map(fn (string $date, int $index): int => DB::table('tournaments')->insertGetId(['discipline_id' => $disciplineId, 'tournament_type_id' => $typeId, 'name' => 'Etapa '.($index + 1), 'end_date' => $date, 'categories' => json_encode([$categoryId]), 'created_at' => now(), 'updated_at' => now()]));

    foreach ($players as $playerIndex => $playerId) {
        foreach ($tournaments as $stageIndex => $tournamentId) {
            $registrationId = DB::table('tournament_registrations')->insertGetId(['tournament_id' => $tournamentId, 'status' => 'aprobado', 'points' => 50, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('tournament_registration_participants')->insert(['tournament_registration_id' => $registrationId, 'player_id' => $playerId, 'position' => 1, 'created_at' => now(), 'updated_at' => now()]);
            $caroms = $playerIndex === 0 ? [50, 40][$stageIndex] : [40, 40][$stageIndex];
            DB::table('three_cushion_stage_results')->insert(['tournament_registration_id' => $registrationId, 'player_id' => $playerId, 'category_id' => $categoryId, 'caroms' => $caroms, 'innings' => 40, 'general_average' => $caroms / 40, 'best_match_average' => $playerIndex === 0 ? 2.1 : 2.0, 'high_run' => $playerIndex === 0 ? 8 : 10, 'high_run_achieved_at' => $stageIndex === 0 ? '2026-03-10' : '2026-06-10', 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    app(ThreeCushionRankingService::class)->syncSeason(2026, $disciplineId);
    $ranking = ThreeCushionRanking::query()->orderBy('position')->get();

    expect($ranking)->toHaveCount(2)
        ->and($ranking[0]->player_id)->toBe($players[0])
        ->and($ranking[0]->total_caroms)->toBe(90)
        ->and($ranking[0]->total_innings)->toBe(80)
        ->and((float) $ranking[0]->general_average)->toBe(1.125)
        ->and((float) $ranking[0]->ranking_points)->toBe(100.0)
        ->and($ranking[0]->stages_played)->toBe(2)
        ->and($ranking[0]->total_stages)->toBe(2);
});
