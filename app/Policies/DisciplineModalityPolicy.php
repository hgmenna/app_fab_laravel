<?php

namespace App\Policies;

use App\Models\DisciplineModality;
use Illuminate\Foundation\Auth\User as AuthUser;

class DisciplineModalityPolicy
{
    public function viewAny(AuthUser $user): bool
    {
        return $user->can('EditField');
    }

    public function view(AuthUser $user, DisciplineModality $record): bool
    {
        return $user->can('EditField');
    }

    public function create(AuthUser $user): bool
    {
        return $user->can('EditField');
    }

    public function update(AuthUser $user, DisciplineModality $record): bool
    {
        return $user->can('EditField');
    }

    public function delete(AuthUser $user, DisciplineModality $record): bool
    {
        return $user->can('EditField');
    }
}
