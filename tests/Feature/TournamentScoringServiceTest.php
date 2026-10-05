<?php

use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentType;
use App\Services\TournamentScoringService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    Schema::dropIfExists('tournament_registrations');
    Schema::dropIfExists('tournament_instances');
    Schema::dropIfExists('tournaments');
    Schema::dropIfExists('tournament_types');

    Schema::create('tournament_types', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->nullable();
        $table->boolean('assigns_points')->default(true);
        $table->boolean('affects_ranking')->default(false);
        $table->string('scoring_method')->nullable();
        $table->json('scoring_rules')->nullable();
        $table->timestamps();
    });

    Schema::create('tournaments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tournament_type_id');
        $table->string('name');
        $table->json('scoring_rules')->nullable();
        $table->timestamps();
    });

    Schema::create('tournament_instances', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('description');
        $table->unsignedInteger('instance');
        $table->decimal('points', 10, 2)->default(0);
        $table->timestamps();
    });

    Schema::create('tournament_registrations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tournament_id');
        $table->foreignId('tournament_instance_id')->nullable();
        $table->string('result_code')->nullable();
        $table->string('result_description')->nullable();
        $table->unsignedInteger('result_instance_value')->nullable();
        $table->decimal('points', 10, 2)->nullable();
        $table->decimal('penalty_points', 10, 2)->default(0);
        $table->boolean('disqualified')->default(false);
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('tournament_registrations');
    Schema::dropIfExists('tournament_instances');
    Schema::dropIfExists('tournaments');
    Schema::dropIfExists('tournament_types');
});

test('CAB asigna los puntos de su regla y conserva la posición oficial', function () {
    $typeId = DB::table('tournament_types')->insertGetId([
        'name' => 'Circuito Argentino de Billar',
        'code' => 'CAB',
        'assigns_points' => true,
        'affects_ranking' => true,
        'scoring_method' => 'position',
        'scoring_rules' => json_encode([
            [
                'tournament_instance_id' => 1,
                'code' => '92',
                'description' => '1°',
                'instance_value' => 92,
                'points' => 75,
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tournamentId = DB::table('tournaments')->insertGetId([
        'tournament_type_id' => $typeId,
        'name' => 'CAB - Etapa 1',
        'scoring_rules' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $instanceId = DB::table('tournament_instances')->insertGetId([
        'code' => '92',
        'description' => '1°',
        'instance' => 92,
        'points' => 75,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $registrationId = DB::table('tournament_registrations')->insertGetId([
        'tournament_id' => $tournamentId,
        'tournament_instance_id' => null,
        'points' => null,
        'penalty_points' => 0,
        'disqualified' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $registration = TournamentRegistration::query()->findOrFail($registrationId);

    app(TournamentScoringService::class)
        ->assignByTournamentInstance($registration, $instanceId);

    $registration->refresh();

    expect($registration->tournament_instance_id)->toBe($instanceId)
        ->and($registration->result_code)->toBe('92')
        ->and($registration->result_description)->toBe('1°')
        ->and($registration->result_instance_value)->toBe(92)
        ->and((float) $registration->points)->toBe(75.0);

    $rules = app(TournamentScoringService::class)
        ->getRules(Tournament::query()->findOrFail($tournamentId));

    expect($rules)->toHaveCount(1)
        ->and($rules[0]['code'])->toBe('92')
        ->and((float) $rules[0]['points'])->toBe(75.0);
});

test('un torneo estadístico guarda su resultado sin posición oficial', function () {
    $typeId = DB::table('tournament_types')->insertGetId([
        'name' => 'Campeonato Argentino',
        'code' => 'ARG',
        'assigns_points' => true,
        'affects_ranking' => false,
        'scoring_method' => 'position',
        'scoring_rules' => json_encode([
            [
                'code' => 'SEMIFINAL',
                'description' => 'Semifinal',
                'instance_value' => 5,
                'points' => 60,
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tournamentId = DB::table('tournaments')->insertGetId([
        'tournament_type_id' => $typeId,
        'name' => 'Campeonato Argentino',
        'scoring_rules' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $registrationId = DB::table('tournament_registrations')->insertGetId([
        'tournament_id' => $tournamentId,
        'tournament_instance_id' => null,
        'points' => null,
        'penalty_points' => 0,
        'disqualified' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $registration = TournamentRegistration::query()->findOrFail($registrationId);

    app(TournamentScoringService::class)
        ->assignByCode($registration, 'SEMIFINAL');

    $registration->refresh();

    expect($registration->tournament_instance_id)->toBeNull()
        ->and($registration->result_code)->toBe('SEMIFINAL')
        ->and($registration->result_description)->toBe('Semifinal')
        ->and($registration->result_instance_value)->toBe(5)
        ->and((float) $registration->points)->toBe(60.0);
});

test('los cambios de la tabla actualizan asignaciones anteriores y futuras', function () {
    $originalRules = [
        [
            'tournament_instance_id' => 1,
            'code' => '92',
            'description' => '1°',
            'instance_value' => 92,
            'points' => 75,
        ],
    ];

    $typeId = DB::table('tournament_types')->insertGetId([
        'name' => 'Circuito Argentino de Billar',
        'code' => 'CAB',
        'assigns_points' => true,
        'affects_ranking' => true,
        'scoring_method' => 'position',
        'scoring_rules' => json_encode($originalRules),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tournamentId = DB::table('tournaments')->insertGetId([
        'tournament_type_id' => $typeId,
        'name' => 'CAB - Etapa 1',
        'scoring_rules' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $instanceId = DB::table('tournament_instances')->insertGetId([
        'code' => '92',
        'description' => '1°',
        'instance' => 92,
        'points' => 75,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $firstRegistrationId = DB::table('tournament_registrations')->insertGetId([
        'tournament_id' => $tournamentId,
        'tournament_instance_id' => null,
        'points' => null,
        'penalty_points' => 0,
        'disqualified' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(TournamentScoringService::class);

    $service->assignByTournamentInstance(
        TournamentRegistration::query()->findOrFail($firstRegistrationId),
        $instanceId,
    );

    $type = TournamentType::query()->findOrFail($typeId);
    $type->scoring_rules = [
        [
            'tournament_instance_id' => $instanceId,
            'code' => '92',
            'description' => '1° modificada',
            'instance_value' => 92,
            'points' => 999,
        ],
    ];
    $type->save();

    $secondRegistrationId = DB::table('tournament_registrations')->insertGetId([
        'tournament_id' => $tournamentId,
        'tournament_instance_id' => null,
        'points' => null,
        'penalty_points' => 0,
        'disqualified' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $secondRegistration = TournamentRegistration::query()
        ->findOrFail($secondRegistrationId);

    $service->assignByTournamentInstance(
        $secondRegistration,
        $instanceId,
    );

    $secondRegistration->refresh();
    $firstRegistration = TournamentRegistration::query()
        ->findOrFail($firstRegistrationId);
    $tournament = Tournament::query()->findOrFail($tournamentId);

    expect((float) $firstRegistration->points)->toBe(999.0)
        ->and($firstRegistration->result_description)->toBe('1° modificada')
        ->and((float) $secondRegistration->points)->toBe(999.0)
        ->and($secondRegistration->result_description)->toBe('1° modificada')
        ->and((float) $tournament->scoring_rules[0]['points'])->toBe(999.0);
});

test('utiliza siempre la tabla vigente del tipo de torneo', function () {
    $typeId = DB::table('tournament_types')->insertGetId([
        'name' => 'Circuito con nuevas posiciones',
        'code' => 'CNP',
        'assigns_points' => true,
        'affects_ranking' => false,
        'scoring_method' => 'position',
        'scoring_rules' => json_encode([
            [
                'code' => 'CAMPEON',
                'description' => 'Campeón actualizado',
                'instance_value' => 1,
                'points' => 999,
            ],
            [
                'code' => 'TERCERO',
                'description' => 'Tercer puesto',
                'instance_value' => 3,
                'points' => 50,
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tournamentId = DB::table('tournaments')->insertGetId([
        'tournament_type_id' => $typeId,
        'name' => 'Torneo iniciado',
        'scoring_rules' => json_encode([[
            'code' => 'CAMPEON',
            'description' => 'Campeón original',
            'instance_value' => 1,
            'points' => 100,
        ]]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $registrationId = DB::table('tournament_registrations')->insertGetId([
        'tournament_id' => $tournamentId,
        'points' => null,
        'penalty_points' => 0,
        'disqualified' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $service = app(TournamentScoringService::class);
    $tournament = Tournament::query()->findOrFail($tournamentId);
    $rules = $service->getRules($tournament);

    $championRule = collect($rules)->firstWhere('code', 'CAMPEON');
    $thirdPlaceRule = collect($rules)->firstWhere('code', 'TERCERO');

    expect($rules)->toHaveCount(2)
        ->and($rules[0]['code'])->toBe('TERCERO')
        ->and($championRule['description'])->toBe('Campeón actualizado')
        ->and((float) $championRule['points'])->toBe(999.0)
        ->and($thirdPlaceRule['description'])->toBe('Tercer puesto');

    $service->assignByCode(
        TournamentRegistration::query()->findOrFail($registrationId),
        'TERCERO',
    );

    $registration = TournamentRegistration::query()->findOrFail($registrationId);

    expect((float) $registration->points)->toBe(50.0)
        ->and($registration->result_description)->toBe('Tercer puesto');
});

test('ordena las reglas por código de mayor a menor', function () {
    $typeId = DB::table('tournament_types')->insertGetId([
        'name' => 'Torneo ordenado',
        'code' => 'ORD',
        'assigns_points' => true,
        'affects_ranking' => false,
        'scoring_method' => 'position',
        'scoring_rules' => json_encode(collect(['9', '100', '92'])->map(fn (string $code): array => [
            'code' => $code,
            'description' => "Código {$code}",
            'instance_value' => (int) $code,
            'points' => (int) $code,
        ])->all()),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tournamentId = DB::table('tournaments')->insertGetId([
        'tournament_type_id' => $typeId,
        'name' => 'Torneo ordenado',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $rules = app(TournamentScoringService::class)
        ->getRules(Tournament::query()->findOrFail($tournamentId));

    expect(collect($rules)->pluck('code')->all())
        ->toBe(['100', '92', '9']);
});

test('una regla inexistente no modifica la inscripción', function () {
    $typeId = DB::table('tournament_types')->insertGetId([
        'name' => 'Torneo Amistad',
        'code' => 'TAM',
        'assigns_points' => true,
        'affects_ranking' => false,
        'scoring_method' => 'position',
        'scoring_rules' => json_encode([
            [
                'code' => 'FINAL',
                'description' => 'Final',
                'instance_value' => 10,
                'points' => 50,
            ],
        ]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tournamentId = DB::table('tournaments')->insertGetId([
        'tournament_type_id' => $typeId,
        'name' => 'Torneo Amistad',
        'scoring_rules' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $registrationId = DB::table('tournament_registrations')->insertGetId([
        'tournament_id' => $tournamentId,
        'tournament_instance_id' => null,
        'result_code' => null,
        'result_description' => null,
        'result_instance_value' => null,
        'points' => null,
        'penalty_points' => 0,
        'disqualified' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $registration = TournamentRegistration::query()->findOrFail($registrationId);

    expect(
        fn () => app(TournamentScoringService::class)
            ->assignByCode($registration, 'NO-EXISTE')
    )->toThrow(ValidationException::class);

    $registration->refresh();
    $tournament = Tournament::query()->findOrFail($tournamentId);

    expect($registration->result_code)->toBeNull()
        ->and($registration->result_description)->toBeNull()
        ->and($registration->result_instance_value)->toBeNull()
        ->and($registration->points)->toBeNull()
        ->and($tournament->scoring_rules)->toBeNull();
});

test('asigna la misma posición a varias inscripciones en un solo paso', function () {
    $typeId = DB::table('tournament_types')->insertGetId([
        'name' => 'Circuito Argentino de Billar',
        'code' => 'CAB',
        'assigns_points' => true,
        'affects_ranking' => true,
        'scoring_method' => 'position',
        'scoring_rules' => json_encode([[
            'tournament_instance_id' => 1,
            'code' => '73',
            'description' => '5° al 8°',
            'instance_value' => 73,
            'points' => 30,
        ]]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tournamentId = DB::table('tournaments')->insertGetId([
        'tournament_type_id' => $typeId,
        'name' => 'CAB - Etapa masiva',
        'scoring_rules' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $instanceId = DB::table('tournament_instances')->insertGetId([
        'code' => '73',
        'description' => '5° al 8°',
        'instance' => 73,
        'points' => 30,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $registrationIds = collect([0, 5])->map(fn (int $penalty): int => DB::table('tournament_registrations')->insertGetId([
        'tournament_id' => $tournamentId,
        'tournament_instance_id' => null,
        'points' => null,
        'penalty_points' => 0,
        'disqualified' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]));

    $updated = app(TournamentScoringService::class)->assignBatch(
        Tournament::query()->findOrFail($tournamentId),
        [
            ['registration_id' => $registrationIds[0], 'penalty_points' => 0],
            ['registration_id' => $registrationIds[1], 'penalty_points' => 5],
        ],
        $instanceId,
    );

    expect($updated)->toHaveCount(2);

    $registrations = TournamentRegistration::query()
        ->whereKey($registrationIds)
        ->orderBy('id')
        ->get();

    expect($registrations[0]->tournament_instance_id)->toBe($instanceId)
        ->and((float) $registrations[0]->points)->toBe(30.0)
        ->and((float) $registrations[0]->penalty_points)->toBe(0.0)
        ->and($registrations[1]->tournament_instance_id)->toBe($instanceId)
        ->and((float) $registrations[1]->points)->toBe(30.0)
        ->and((float) $registrations[1]->penalty_points)->toBe(5.0);
});

test('rechaza filas repetidas sin modificar ninguna inscripción', function () {
    $typeId = DB::table('tournament_types')->insertGetId([
        'name' => 'Campeonato Argentino',
        'code' => 'ARG',
        'assigns_points' => true,
        'affects_ranking' => false,
        'scoring_method' => 'position',
        'scoring_rules' => json_encode([[
            'code' => 'SEMIFINAL',
            'description' => 'Semifinal',
            'instance_value' => 5,
            'points' => 60,
        ]]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tournamentId = DB::table('tournaments')->insertGetId([
        'tournament_type_id' => $typeId,
        'name' => 'Argentino',
        'scoring_rules' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $registrationId = DB::table('tournament_registrations')->insertGetId([
        'tournament_id' => $tournamentId,
        'tournament_instance_id' => null,
        'points' => null,
        'penalty_points' => 0,
        'disqualified' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => app(TournamentScoringService::class)->assignBatch(
        Tournament::query()->findOrFail($tournamentId),
        [
            ['registration_id' => $registrationId, 'penalty_points' => 0],
            ['registration_id' => $registrationId, 'penalty_points' => 0],
        ],
        resultCode: 'SEMIFINAL',
    ))->toThrow(ValidationException::class);

    $registration = TournamentRegistration::query()->findOrFail($registrationId);

    expect($registration->result_code)->toBeNull()
        ->and($registration->points)->toBeNull();
});

test('no permite incluir una inscripción que ya tiene puntuación', function () {
    $typeId = DB::table('tournament_types')->insertGetId([
        'name' => 'Torneo puntuable',
        'code' => 'TP',
        'assigns_points' => true,
        'affects_ranking' => false,
        'scoring_method' => 'position',
        'scoring_rules' => json_encode([[
            'code' => 'FINALISTA',
            'description' => 'Finalista',
            'instance_value' => 2,
            'points' => 80,
        ]]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $tournamentId = DB::table('tournaments')->insertGetId([
        'tournament_type_id' => $typeId,
        'name' => 'Torneo con puntuación previa',
        'scoring_rules' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $registrationId = DB::table('tournament_registrations')->insertGetId([
        'tournament_id' => $tournamentId,
        'points' => 25,
        'penalty_points' => 0,
        'disqualified' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => app(TournamentScoringService::class)->assignBatch(
        Tournament::query()->findOrFail($tournamentId),
        [['registration_id' => $registrationId, 'penalty_points' => 0]],
        resultCode: 'FINALISTA',
    ))->toThrow(ValidationException::class);

    $registration = TournamentRegistration::query()->findOrFail($registrationId);

    expect((float) $registration->points)->toBe(25.0)
        ->and($registration->result_code)->toBeNull();
});
