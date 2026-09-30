<?php

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Tournament;
use App\Models\User;
use App\Policies\TournamentPolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (['discipline_user_roles', 'model_has_roles', 'role_has_permissions', 'permissions', 'roles', 'tournaments', 'disciplines', 'users'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email');
        $table->string('password');
        $table->timestamps();
    });
    Schema::create('disciplines', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
    });
    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
    });
    Schema::create('permissions', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
    });
    Schema::create('role_has_permissions', function (Blueprint $table): void {
        $table->foreignId('permission_id');
        $table->foreignId('role_id');
    });
    Schema::create('model_has_roles', function (Blueprint $table): void {
        $table->foreignId('role_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
    });
    Schema::create('discipline_user_roles', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('discipline_id');
        $table->foreignId('role_id');
        $table->boolean('is_active');
        $table->timestamps();
    });
    Schema::create('tournaments', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('discipline_id');
        $table->string('name');
        $table->timestamps();
    });

    DB::table('users')->insert(['id' => 1, 'name' => 'Administrador', 'email' => 'admin@example.test', 'password' => 'secret', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('disciplines')->insert([['id' => 1, 'name' => 'Pool'], ['id' => 2, 'name' => 'Carambola'], ['id' => 3, 'name' => '5 Quillas'], ['id' => 4, 'name' => 'Snooker']]);
    DB::table('roles')->insert([
        ['id' => 1, 'name' => 'Administrador de disciplina', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Consulta', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('permissions')->insert([
        ['id' => 1, 'name' => 'ViewAny:Tournament', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Update:Tournament', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('role_has_permissions')->insert([
        ['permission_id' => 1, 'role_id' => 1],
        ['permission_id' => 2, 'role_id' => 1],
        ['permission_id' => 1, 'role_id' => 2],
    ]);
    DB::table('discipline_user_roles')->insert([
        ['user_id' => 1, 'discipline_id' => 1, 'role_id' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => 1, 'discipline_id' => 2, 'role_id' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['user_id' => 1, 'discipline_id' => 3, 'role_id' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('tournaments')->insert([
        ['id' => 1, 'discipline_id' => 1, 'name' => 'Pool', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'discipline_id' => 2, 'name' => 'Carambola', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'discipline_id' => 3, 'name' => '5 Quillas', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 4, 'discipline_id' => 4, 'name' => 'Snooker', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('allows different Shield roles in multiple disciplines without leaking update permission', function () {
    $user = User::findOrFail(1);
    $policy = new TournamentPolicy;

    expect($user->allowedDisciplineIds('ViewAny:Tournament'))->toBe([1, 2, 3])
        ->and($user->allowedDisciplineIds('Update:Tournament'))->toBe([1, 2])
        ->and($policy->update($user, Tournament::findOrFail(1)))->toBeTrue()
        ->and($policy->update($user, Tournament::findOrFail(2)))->toBeTrue()
        ->and($policy->update($user, Tournament::findOrFail(3)))->toBeFalse()
        ->and($policy->view($user, Tournament::findOrFail(4)))->toBeFalse();
});

it('limits Filament tournament queries to assigned disciplines', function () {
    $this->actingAs(User::findOrFail(1));

    expect(TournamentResource::getEloquentQuery()->pluck('id')->all())->toBe([1, 2, 3]);
});
