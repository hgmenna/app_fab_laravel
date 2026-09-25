<?php

namespace App\Policies;

use App\Models\TournamentRegulationSetting;
use App\Models\User;

class TournamentRegulationSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function view(User $user, TournamentRegulationSetting $record): bool
    {
        return $user->hasRole('super-admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, TournamentRegulationSetting $record): bool
    {
        return $user->hasRole('super-admin');
    }

    public function delete(User $user, TournamentRegulationSetting $record): bool
    {
        return $user->hasRole('super-admin');
    }
}
