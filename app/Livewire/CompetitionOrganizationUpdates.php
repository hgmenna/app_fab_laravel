<?php

namespace App\Livewire;

use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasMatch;
use App\Models\FiveQuillasPhase;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Consulta cambios sin volver a renderizar la grilla en cada intervalo. */
#[Isolate]
class CompetitionOrganizationUpdates extends Component
{
    #[Locked]
    public int $tournamentId;

    #[Locked]
    public ?string $revision = null;

    public static function revisionKey(int $tournamentId): string
    {
        return 'tournament-organization:'.$tournamentId;
    }

    public static function revisionFor(int $tournamentId): string
    {
        $competition = FiveQuillasCompetition::query()->where('tournament_id', $tournamentId)->select('id');
        $phases = FiveQuillasPhase::query()->whereIn('competition_id', $competition)->select('id');
        // Se consulta el marcador persistido para detectar cambios incluso si
        // no se publicó una notificación o la caché no se comparte.
        $matches = FiveQuillasMatch::query()->whereIn('phase_id', $phases)->orderBy('id')->toBase()->get([
            'id', 'phase_id', 'status', 'set_scores', 'winner_entry_id', 'is_bye',
            'player_one_entry_id', 'player_two_entry_id', 'scheduled_at', 'table_number',
        ]);

        return hash('sha256', serialize([$matches->all(), Cache::get(self::revisionKey($tournamentId))]));
    }

    public function checkForChanges(): void
    {
        $revision = self::revisionFor($this->tournamentId);
        if ($revision !== $this->revision) {
            $this->revision = $revision;
            $this->dispatch('competition-organization-changed');
        }

        $this->skipRender();
    }

    public function render(): string
    {
        return '<span wire:poll.5s.keep-alive="checkForChanges" aria-hidden="true"></span>';
    }
}
