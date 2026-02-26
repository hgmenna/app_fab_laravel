<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;

class DisableShieldAuthForGuestPanel
{
    public function handle($request, Closure $next)
    {
        $panel = Filament::getCurrentPanel();

        if ($panel && $panel->getId() === 'guest') {
            // Desactiva la autenticación de Shield SOLO en este panel
            config(['filament-shield.auth.enabled' => false]);
        }

        return $next($request);
    }
}