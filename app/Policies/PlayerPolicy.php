<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Player;
use App\Models\User;
use App\Policies\Concerns\ChecksDisciplinePermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlayerPolicy
{
    use ChecksDisciplinePermissions, HandlesAuthorization;

    public function viewAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'ViewAny:Player');
    }

    public function view(User $authUser, Player $player): bool
    {
        return $this->allowsFor($authUser, 'View:Player', $player->discipline_id);
    }

    public function create(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'Create:Player');
    }

    public function update(User $authUser, Player $player): bool
    {
        if ($authUser->hasRole('federacion')) {
            return $authUser->name === $player->club?->city?->state?->federation?->short_name;
        }

        return $this->allowsFor($authUser, 'Update:Player', $player->discipline_id);
    }

    public function delete(User $authUser, Player $player): bool
    {
        return $this->allowsFor($authUser, 'Delete:Player', $player->discipline_id);
    }

    public function restore(User $authUser, Player $player): bool
    {
        return $this->allowsFor($authUser, 'Restore:Player', $player->discipline_id);
    }

    public function forceDelete(User $authUser, Player $player): bool
    {
        return $this->allowsFor($authUser, 'ForceDelete:Player', $player->discipline_id);
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'ForceDeleteAny:Player');
    }

    public function restoreAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'RestoreAny:Player');
    }

    public function replicate(User $authUser, Player $player): bool
    {
        return $this->allowsFor($authUser, 'Replicate:Player', $player->discipline_id);
    }

    public function reorder(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'Reorder:Player');
    }
}
