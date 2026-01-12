<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Federation;
use Illuminate\Auth\Access\HandlesAuthorization;

class FederationPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Federation');
    }

    public function view(AuthUser $authUser, Federation $federation): bool
    {
        return $authUser->can('View:Federation');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Federation');
    }

    public function update(AuthUser $authUser, Federation $federation): bool
    {
        return $authUser->can('Update:Federation');
    }

    public function delete(AuthUser $authUser, Federation $federation): bool
    {
        return $authUser->can('Delete:Federation');
    }

    public function restore(AuthUser $authUser, Federation $federation): bool
    {
        return $authUser->can('Restore:Federation');
    }

    public function forceDelete(AuthUser $authUser, Federation $federation): bool
    {
        return $authUser->can('ForceDelete:Federation');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Federation');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Federation');
    }

    public function replicate(AuthUser $authUser, Federation $federation): bool
    {
        return $authUser->can('Replicate:Federation');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Federation');
    }

}