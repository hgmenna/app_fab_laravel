<?php

namespace App\Livewire;

use App\Models\MarkerTournamentLink;
use App\Models\Tournament;
use App\Models\User;
use App\Services\MarkerApi;
use App\Services\TournamentMarkerService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use RuntimeException;

#[Isolate]
class TournamentMarkerLive extends Component
{
    #[Locked]
    public int $tournamentId;

    #[Locked]
    public ?string $error = null;

    #[Locked]
    public string $club = '';

    #[Locked]
    public int $conflicts = 0;

    public function mount(int $tournamentId): void
    {
        $this->tournamentId = $tournamentId;
        $this->pollScores();
    }

    public function pollScores(): void
    {
        $user = Auth::user();
        $tournament = Tournament::query()->findOrFail($this->tournamentId);
        abort_unless($user instanceof User && $user->canAccessTournament('ManageTournamentCompetition', $tournament), 403);

        if (! app(MarkerApi::class)->availableFor($this->tournamentId)) {
            $this->error = 'El marcador de prueba ya no está disponible para este torneo.';
            $this->dispatch('marker-live', scores: (object) []);

            return;
        }
        $link = MarkerTournamentLink::query()->where('tournament_id', $this->tournamentId)->firstOrFail();
        $this->club = $link->club_name;
        $this->error = null;
        $scores = [];
        try {
            $assignments = $link->assignments()->whereIn('status', ['pending', 'sent'])->get();
            $live = app(TournamentMarkerService::class)->live($link, $assignments);
            foreach ($assignments as $assignment) {
                if (isset($live[$assignment->id])) {
                    $scores[(string) $assignment->five_quillas_match_id] = $live[$assignment->id];
                }
            }
        } catch (RuntimeException $exception) {
            $this->error = $exception->getMessage();
        }
        $this->conflicts = $link->assignments()->where('status', 'conflict')->count();

        $this->dispatch('marker-live', scores: (object) $scores);
    }

    public function render()
    {
        return view('livewire.tournament-marker-live');
    }
}
