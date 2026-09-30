<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TournamentType;
use App\Models\User;
use App\Policies\Concerns\ChecksDisciplinePermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class TournamentTypePolicy
{
    use ChecksDisciplinePermissions, HandlesAuthorization;

    public function viewAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'ViewAny:TournamentType');
    }

    public function view(User $authUser, TournamentType $tournamentType): bool
    {
        return $this->allowsFor($authUser, 'View:TournamentType', $tournamentType->discipline_id);
    }

    public function create(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'Create:TournamentType');
    }

    public function update(User $authUser, TournamentType $tournamentType): bool
    {
        return $this->allowsFor($authUser, 'Update:TournamentType', $tournamentType->discipline_id);
    }

    public function delete(User $authUser, TournamentType $tournamentType): bool
    {
        return $this->allowsFor($authUser, 'Delete:TournamentType', $tournamentType->discipline_id);
    }

    public function restore(User $authUser, TournamentType $tournamentType): bool
    {
        return $this->allowsFor($authUser, 'Restore:TournamentType', $tournamentType->discipline_id);
    }

    public function forceDelete(User $authUser, TournamentType $tournamentType): bool
    {
        return $this->allowsFor($authUser, 'ForceDelete:TournamentType', $tournamentType->discipline_id);
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'ForceDeleteAny:TournamentType');
    }

    public function restoreAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'RestoreAny:TournamentType');
    }

    public function replicate(User $authUser, TournamentType $tournamentType): bool
    {
        return $this->allowsFor($authUser, 'Replicate:TournamentType', $tournamentType->discipline_id);
    }

    public function reorder(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'Reorder:TournamentType');
    }
}
