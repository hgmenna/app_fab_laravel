<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Player;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlayerPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Player');
    }

    public function view(AuthUser $authUser, Player $player): bool
    {
        return $authUser->can('View:Player');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Player');
    }

    public function update(AuthUser $authUser, Player $player): bool
    {
        return $authUser->can('Update:Player');
    }

    public function delete(AuthUser $authUser, Player $player): bool
    {
        return $authUser->can('Delete:Player');
    }

    public function restore(AuthUser $authUser, Player $player): bool
    {
        return $authUser->can('Restore:Player');
    }

    public function forceDelete(AuthUser $authUser, Player $player): bool
    {
        return $authUser->can('ForceDelete:Player');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Player');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Player');
    }

    public function replicate(AuthUser $authUser, Player $player): bool
    {
        return $authUser->can('Replicate:Player');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Player');
    }

}