<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MobileTournamentOrganizationController extends Controller
{
    public function __invoke(Request $request, Tournament $tournament): Response
    {
        $user = $request->user();
        abort_unless($user?->hasVerifiedEmail() && $user->canAccessTournament('ManageTournamentCompetition', $tournament), 403);
        abort_unless($tournament->discipline?->code === 'five_quillas', 404);

        $competition = $tournament->fiveQuillasCompetition;
        $phases = $competition?->phases()->where('is_enabled', true)->get() ?? collect();
        $phase = $phases->firstWhere('id', $request->integer('fase')) ?? $phases->first();
        $matches = $phase?->matches()->where('is_bye', false)->with(['playerOne.player', 'playerTwo.player'])
            ->simplePaginate(15)->withQueryString();

        return response()->view('mobile-tournament-organization', compact('tournament', 'phases', 'phase', 'matches'))
            ->header('Cache-Control', 'private, no-store');
    }
}
