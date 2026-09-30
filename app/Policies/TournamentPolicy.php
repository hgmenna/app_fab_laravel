<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Tournament;
use App\Models\User;
use App\Policies\Concerns\ChecksDisciplinePermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class TournamentPolicy
{
    use ChecksDisciplinePermissions, HandlesAuthorization;

    public function viewAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'ViewAny:Tournament');
    }

    public function view(User $authUser, Tournament $tournament): bool
    {
        return $this->allowsFor($authUser, 'View:Tournament', $tournament->discipline_id);
    }

    public function create(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'Create:Tournament');
    }

    public function update(User $authUser, Tournament $tournament): bool
    {
        if ($authUser->hasRole('federacion')) {
            return $authUser->name === $tournament->venue?->city?->state?->federation?->short_name;
        }

        return $this->allowsFor($authUser, 'Update:Tournament', $tournament->discipline_id);
    }

    public function delete(User $authUser, Tournament $tournament): bool
    {
        return $this->allowsFor($authUser, 'Delete:Tournament', $tournament->discipline_id);
    }

    public function restore(User $authUser, Tournament $tournament): bool
    {
        return $this->allowsFor($authUser, 'Restore:Tournament', $tournament->discipline_id);
    }

    public function forceDelete(User $authUser, Tournament $tournament): bool
    {
        return $this->allowsFor($authUser, 'ForceDelete:Tournament', $tournament->discipline_id);
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'ForceDeleteAny:Tournament');
    }

    public function restoreAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'RestoreAny:Tournament');
    }

    public function replicate(User $authUser, Tournament $tournament): bool
    {
        return $this->allowsFor($authUser, 'Replicate:Tournament', $tournament->discipline_id);
    }

    public function reorder(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'Reorder:Tournament');
    }
}
