<?php

namespace App\Auth;

use App\Models\User;
use Spatie\Permission\Models\Role;

class GuestUser extends User
{
    protected $attributes = [
        'name' => 'Invitado',
        'email' => 'guest@example.com',
    ];

    public function __construct()
    {
        parent::__construct();

        // Cargar el rol "Jugador" y asignarlo como relación
        $role = Role::where('name', 'Jugador')->first();

        if ($role) {
            // Simula que el invitado tiene el rol Jugador
            $this->setRelation('roles', collect([$role]));
        }
    }

    /**
     * ID virtual para que Filament/Livewire no rompan.
     */
    public function getAuthIdentifier()
    {
        return 0;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }
}