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
        return $authUser->canAccessClubInDiscipline('View:Tournament', $tournament->discipline_id, $this->clubId($tournament));
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

        return $authUser->canAccessClubInDiscipline('Update:Tournament', $tournament->discipline_id, $this->clubId($tournament));
    }

    public function delete(User $authUser, Tournament $tournament): bool
    {
        return $authUser->canAccessClubInDiscipline('Delete:Tournament', $tournament->discipline_id, $this->clubId($tournament));
    }

    public function restore(User $authUser, Tournament $tournament): bool
    {
        return $authUser->canAccessClubInDiscipline('Restore:Tournament', $tournament->discipline_id, $this->clubId($tournament));
    }

    public function forceDelete(User $authUser, Tournament $tournament): bool
    {
        return $authUser->canAccessClubInDiscipline('ForceDelete:Tournament', $tournament->discipline_id, $this->clubId($tournament));
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
        return $authUser->canAccessClubInDiscipline('Replicate:Tournament', $tournament->discipline_id, $this->clubId($tournament));
    }

    public function reorder(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'Reorder:Tournament');
    }

    private function clubId(Tournament $tournament): ?int
    {
        return $tournament->venue_type === 'club' && filled($tournament->venue_id)
            ? (int) $tournament->venue_id
            : null;
    }
}
