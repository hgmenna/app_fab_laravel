<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\TournamentInstance;
use Illuminate\Auth\Access\HandlesAuthorization;

class TournamentInstancePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:TournamentInstance');
    }

    public function view(AuthUser $authUser, TournamentInstance $tournamentInstance): bool
    {
        return $authUser->can('View:TournamentInstance');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:TournamentInstance');
    }

    public function update(AuthUser $authUser, TournamentInstance $tournamentInstance): bool
    {
        return $authUser->can('Update:TournamentInstance');
    }

    public function delete(AuthUser $authUser, TournamentInstance $tournamentInstance): bool
    {
        return $authUser->can('Delete:TournamentInstance');
    }

    public function restore(AuthUser $authUser, TournamentInstance $tournamentInstance): bool
    {
        return $authUser->can('Restore:TournamentInstance');
    }

    public function forceDelete(AuthUser $authUser, TournamentInstance $tournamentInstance): bool
    {
        return $authUser->can('ForceDelete:TournamentInstance');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:TournamentInstance');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:TournamentInstance');
    }

    public function replicate(AuthUser $authUser, TournamentInstance $tournamentInstance): bool
    {
        return $authUser->can('Replicate:TournamentInstance');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:TournamentInstance');
    }

}