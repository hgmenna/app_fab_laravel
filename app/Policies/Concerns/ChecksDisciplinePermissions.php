<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksDisciplinePermissions
{
    protected function allowsAny(User $user, string $permission): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasActiveDisciplineAssignments()
            ? $user->hasPermissionInAnyDiscipline($permission)
            : $user->can($permission);
    }

    protected function allowsFor(User $user, string $permission, int|string|null $disciplineId): bool
    {
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return $user->hasActiveDisciplineAssignments()
            ? $user->hasDisciplinePermission($permission, $disciplineId)
            : $user->can($permission);
    }
}
