<?php

namespace App\Livewire;

use App\Helpers\CompetitionViewVersion;
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

    #[Locked]
    public ?string $viewVersion = null;

    public static function revisionKey(int $tournamentId): string
    {
        return 'tournament-organization:'.$tournamentId;
    }

    public static function revisionFor(int $tournamentId, ?string $viewVersion = null): string
    {
        $competition = FiveQuillasCompetition::query()->where('tournament_id', $tournamentId)->select('id');
        $phases = FiveQuillasPhase::query()->whereIn('competition_id', $competition)->select('id');
        // Se consulta el marcador persistido para detectar cambios incluso si
        // no se publicó una notificación o la caché no se comparte.
        $matches = FiveQuillasMatch::query()->whereIn('phase_id', $phases)->orderBy('id')->toBase()->get([
            'id', 'phase_id', 'status', 'set_scores', 'winner_entry_id', 'is_bye',
            'player_one_entry_id', 'player_two_entry_id', 'scheduled_at', 'table_number',
        ]);

        // Las pestañas anteriores al versionado también pueden recibir el
        // diseño nuevo a través de su actualización de resultados habitual.
        return hash('sha256', serialize([
            $matches->all(),
            Cache::get(self::revisionKey($tournamentId)),
            $viewVersion ?? CompetitionViewVersion::current(organization: true),
        ]));
    }

    public function checkForChanges(): void
    {
        $this->skipRender();
        $viewVersion = CompetitionViewVersion::current(organization: true);
        if ($this->viewVersion !== null && $this->viewVersion !== $viewVersion) {
            $this->dispatch('competition-organization-view-changed', version: $viewVersion);

            return;
        }

        $revision = self::revisionFor($this->tournamentId, $viewVersion);
        if ($revision !== $this->revision) {
            $this->revision = $revision;
            $this->dispatch('competition-organization-changed');
        }
    }

    public function render(): string
    {
        return '<span wire:poll.5s.keep-alive="checkForChanges" aria-hidden="true"></span>';
    }
}
