<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Discipline;
use App\Models\User;
use App\Policies\Concerns\ChecksDisciplinePermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class DisciplinePolicy
{
    use ChecksDisciplinePermissions, HandlesAuthorization;

    public function viewAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'ViewAny:Discipline');
    }

    public function view(User $authUser, Discipline $discipline): bool
    {
        return $this->allowsFor($authUser, 'View:Discipline', $discipline->id);
    }

    public function create(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'Create:Discipline');
    }

    public function update(User $authUser, Discipline $discipline): bool
    {
        return $this->allowsFor($authUser, 'Update:Discipline', $discipline->id);
    }

    public function delete(User $authUser, Discipline $discipline): bool
    {
        return $this->allowsFor($authUser, 'Delete:Discipline', $discipline->id);
    }

    public function restore(User $authUser, Discipline $discipline): bool
    {
        return $this->allowsFor($authUser, 'Restore:Discipline', $discipline->id);
    }

    public function forceDelete(User $authUser, Discipline $discipline): bool
    {
        return $this->allowsFor($authUser, 'ForceDelete:Discipline', $discipline->id);
    }

    public function forceDeleteAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'ForceDeleteAny:Discipline');
    }

    public function restoreAny(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'RestoreAny:Discipline');
    }

    public function replicate(User $authUser, Discipline $discipline): bool
    {
        return $this->allowsFor($authUser, 'Replicate:Discipline', $discipline->id);
    }

    public function reorder(User $authUser): bool
    {
        return $this->allowsAny($authUser, 'Reorder:Discipline');
    }
}
