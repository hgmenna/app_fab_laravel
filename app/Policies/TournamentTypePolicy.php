<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TournamentType;
use Illuminate\Auth\Access\HandlesAuthorization;

class TournamentTypePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TournamentType');
    }

    public function view(AuthUser $authUser, TournamentType $tournamentType): bool
    {
        return $authUser->can('View:TournamentType');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TournamentType');
    }

    public function update(AuthUser $authUser, TournamentType $tournamentType): bool
    {
        return $authUser->can('Update:TournamentType');
    }

    public function delete(AuthUser $authUser, TournamentType $tournamentType): bool
    {
        return $authUser->can('Delete:TournamentType');
    }

    public function restore(AuthUser $authUser, TournamentType $tournamentType): bool
    {
        return $authUser->can('Restore:TournamentType');
    }

    public function forceDelete(AuthUser $authUser, TournamentType $tournamentType): bool
    {
        return $authUser->can('ForceDelete:TournamentType');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TournamentType');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TournamentType');
    }

    public function replicate(AuthUser $authUser, TournamentType $tournamentType): bool
    {
        return $authUser->can('Replicate:TournamentType');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TournamentType');
    }

}