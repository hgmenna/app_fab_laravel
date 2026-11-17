<?php

use App\Auth\GuestUser;
use App\Models\User;

it('uses user_id for discipline assignments on registered users and virtual guests', function () {
    // Evita la consulta del constructor del invitado: esta prueba inspecciona la relación heredada.
    $guest = (new ReflectionClass(GuestUser::class))->newInstanceWithoutConstructor();

    expect((new User)->disciplineAssignments()->getForeignKeyName())->toBe('user_id')
        ->and($guest->disciplineAssignments()->getForeignKeyName())->toBe('user_id')
        ->and($guest->disciplineAssignments()->toSql())->not->toContain('guest_user_id');
});
