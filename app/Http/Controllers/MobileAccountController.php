<?php

namespace App\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class MobileAccountController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user(Filament::getPanel('admin')->getAuthGuard());

        return response()->view('mobile-account', compact('user'))->header('Cache-Control', 'private, no-store');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:4096'],
        ]);
        $panel = Filament::getPanel('admin');
        $guard = Auth::guard($panel->getAuthGuard());

        if (! $guard->attemptWhen($credentials, fn ($user) => $user->canAccessPanel($panel) && $user->hasVerifiedEmail())) {
            throw ValidationException::withMessages(['name' => 'No se pudo ingresar. Revisá tus datos y que tu cuenta esté habilitada y verificada.']);
        }

        $request->session()->regenerate();

        return redirect()->route('mobile-app');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard(Filament::getPanel('admin')->getAuthGuard())->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('mobile-app');
    }
}
