<?php

namespace App\Policies;

use App\Models\Category;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class CategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $user): bool
    {
        return $user->can('ViewAny:Category') || $user->can('EditField');
    }

    public function view(AuthUser $user, Category $category): bool
    {
        return $user->can('View:Category') || $user->can('EditField');
    }

    public function create(AuthUser $user): bool
    {
        return $user->can('Create:Category') || $user->can('EditField');
    }

    public function update(AuthUser $user, Category $category): bool
    {
        return $user->can('Update:Category') || $user->can('EditField');
    }

    public function delete(AuthUser $user, Category $category): bool
    {
        return $user->can('Delete:Category') || $user->can('EditField');
    }
}
