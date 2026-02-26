<?php

namespace App\Policies;

use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(?User $authUser): bool
    {
        return $authUser?->can('ViewAny:User') ?? false;
    }

    public function view(?User $authUser): bool
    {
        return $authUser?->can('View:User') ?? false;
    }

    public function create(?User $authUser): bool
    {
        return $authUser?->can('Create:User') ?? false;
    }

    public function update(?User $authUser): bool
    {
        return $authUser?->can('Update:User') ?? false;
    }

    public function delete(?User $authUser): bool
    {
        return $authUser?->can('Delete:User') ?? false;
    }

    public function restore(?User $authUser): bool
    {
        return $authUser?->can('Restore:User') ?? false;
    }

    public function forceDelete(?User $authUser): bool
    {
        return $authUser?->can('ForceDelete:User') ?? false;
    }

    public function forceDeleteAny(?User $authUser): bool
    {
        return $authUser?->can('ForceDeleteAny:User') ?? false;
    }

    public function restoreAny(?User $authUser): bool
    {
        return $authUser?->can('RestoreAny:User') ?? false;
    }

    public function replicate(?User $authUser): bool
    {
        return $authUser?->can('Replicate:User') ?? false;
    }

    public function reorder(?User $authUser): bool
    {
        return $authUser?->can('Reorder:User') ?? false;
    }

}