<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class AssignGuestUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guest()) {
            $guest = new User();
            
            // ASIGNACIÓN NECESARIA: 
            // Filament requiere un string para el nombre del usuario [1, 2].
            $guest->name = 'Invitado'; 
            
            // Opcional: puedes asignar un ID ficticio si algún recurso lo requiere
            $guest->id = 0; 

            Auth::setUser($guest);
        }

        return $next($request);
    }
}
