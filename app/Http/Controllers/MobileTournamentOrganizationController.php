<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Filament\Facades\Filament;

class MobileTournamentOrganizationController extends Controller
{
    public function __invoke(Request $request, Tournament $tournament): Response
    {
        $user = $request->user();
        abort_unless($user?->hasVerifiedEmail() && $user->canAccessTournament('ManageTournamentCompetition', $tournament), 403);
        abort_unless($tournament->discipline?->code === 'five_quillas', 404);

        // Los formularios reutilizan las acciones del panel, dentro de una interfaz exclusiva de la app.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();

        return response()->view('mobile-tournament-organization', compact('tournament'))
            ->header('Cache-Control', 'private, no-store');
    }
}
