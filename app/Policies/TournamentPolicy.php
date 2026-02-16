<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Tournament;
use Illuminate\Auth\Access\HandlesAuthorization;

class TournamentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Tournament');
    }

    public function view(AuthUser $authUser, Tournament $tournament): bool
    {
        return $authUser->can('View:Tournament');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Tournament');
    }

    public function update(AuthUser $authUser, Tournament $tournament): bool
    {
        if ($authUser->hasRole('federacion')) 
        {
            return $authUser->name === $tournament->venue?->city?->state?->federation?->short_name;
        }
        return $authUser->can('Update:Tournament');
    }

    public function delete(AuthUser $authUser, Tournament $tournament): bool
    {
        return $authUser->can('Delete:Tournament');
    }

    public function restore(AuthUser $authUser, Tournament $tournament): bool
    {
        return $authUser->can('Restore:Tournament');
    }

    public function forceDelete(AuthUser $authUser, Tournament $tournament): bool
    {
        return $authUser->can('ForceDelete:Tournament');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Tournament');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Tournament');
    }

    public function replicate(AuthUser $authUser, Tournament $tournament): bool
    {
        return $authUser->can('Replicate:Tournament');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Tournament');
    }

}