<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\TournamentType;
use App\Auth\GuestUser;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Response;

class MobileAppController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $panel = Filament::getPanel('admin');
        $user = $request->user($panel->getAuthGuard());
        $canManage = $user && $user->hasVerifiedEmail() && $user->canAccessPanel($panel);
        $canSeePlayers = Gate::forUser($canManage ? $user : new GuestUser)
            ->allows('viewAny', \App\Models\Player::class);

        return response()->view('mobile-app', [
            'disciplines' => Discipline::query()->orderBy('name')->get(['id', 'name']),
            'types' => TournamentType::query()->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->orderBy('name')->get(['id', 'discipline_id', 'name']),

            'playersUrl' => $canSeePlayers ? route('mobile-players.index') : null,
            'manageUrl' => route('mobile-account.show'),
            'canManage' => $canManage,
        ])->header('Cache-Control', 'private, no-store');
    }
}
