<?php

namespace App\Filament\Resources\TournamentRegistrations\Traits;

trait DetectsNestedContext
{
    public static function isNested($livewire): bool
    {
        return filled($livewire->ownerRecord ?? null);
    }
}

