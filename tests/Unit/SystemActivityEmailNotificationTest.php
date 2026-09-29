<?php

use App\Models\User;
use App\Notifications\SystemActivityEmailNotification;

uses(Tests\TestCase::class);

it('builds the system activity email for the recipient', function () {
    $user = new User([
        'name' => 'Usuario de prueba',
        'email' => 'usuario@example.com',
    ]);

    $mail = (new SystemActivityEmailNotification(
        'Operación: Creó',
        'El usuario creó un torneo.',
    ))->toMail($user);

    expect($mail->subject)
        ->toBe('Operación: Creó | Federación Argentina de Billar')
        ->and($mail->greeting)
        ->toBe('Hola Usuario de prueba')
        ->and($mail->introLines)
        ->toContain('El usuario creó un torneo.');
});
