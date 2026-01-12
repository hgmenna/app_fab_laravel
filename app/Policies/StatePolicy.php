<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\State;
use Illuminate\Auth\Access\HandlesAuthorization;

class StatePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:State');
    }

    public function view(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('View:State');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:State');
    }

    public function update(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('Update:State');
    }

    public function delete(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('Delete:State');
    }

    public function restore(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('Restore:State');
    }

    public function forceDelete(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('ForceDelete:State');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:State');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:State');
    }

    public function replicate(AuthUser $authUser, State $state): bool
    {
        return $authUser->can('Replicate:State');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:State');
    }

}