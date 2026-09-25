<?php

namespace App\Policies;

use App\Models\TournamentRegulationAudit;
use App\Models\User;

class TournamentRegulationAuditPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function view(User $user, TournamentRegulationAudit $record): bool
    {
        return $user->hasRole('super-admin');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TournamentRegulationAudit $record): bool
    {
        return false;
    }

    public function delete(User $user, TournamentRegulationAudit $record): bool
    {
        return false;
    }
}
