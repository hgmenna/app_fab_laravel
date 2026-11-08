<?php

namespace App\Livewire;

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

    public function checkForChanges(): void
    {
        $revision = Cache::get(self::revisionKey($this->tournamentId));
        if ($revision !== $this->revision) {
            $this->revision = $revision;
            $this->dispatch('competition-organization-changed');
        }

        $this->skipRender();
    }

    public function render(): string
    {
        return '<span wire:poll.5s="checkForChanges" aria-hidden="true"></span>';
    }
}
