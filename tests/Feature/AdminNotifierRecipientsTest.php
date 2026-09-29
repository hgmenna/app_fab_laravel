<?php

use App\Models\User;
use App\Services\AdminNotifier;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Schema::dropIfExists('model_has_roles');
    Schema::dropIfExists('roles');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });

    Schema::create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('guard_name');
        $table->timestamps();
        $table->unique(['name', 'guard_name']);
    });

    Schema::create('model_has_roles', function (Blueprint $table): void {
        $table->unsignedBigInteger('role_id');
        $table->string('model_type');
        $table->unsignedBigInteger('model_id');
        $table->primary(['role_id', 'model_id', 'model_type']);
    });

    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

afterEach(function () {
    Auth::logout();
    Schema::dropIfExists('model_has_roles');
    Schema::dropIfExists('roles');
    Schema::dropIfExists('users');
});

it('returns the logged user and every super admin without duplicates', function () {
    $actor = User::query()->create([
        'name' => 'Operador',
        'email' => 'operador@example.com',
        'password' => 'password',
    ]);
    $admin = User::query()->create([
        'name' => 'Administrador general',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $role = Role::query()->create(['name' => 'super-admin', 'guard_name' => 'web']);
    $admin->assignRole($role);
    Auth::login($actor);

    expect(AdminNotifier::recipientEmails())
        ->toBe(['operador@example.com', 'admin@example.com']);
});

it('does not duplicate the email when the logged user is the super admin', function () {
    $admin = User::query()->create([
        'name' => 'super-admin',
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);
    Auth::login($admin);

    expect(AdminNotifier::recipientEmails())
        ->toBe(['admin@example.com']);
});
