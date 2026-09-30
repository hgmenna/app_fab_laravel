<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\ChecksDisciplinePermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use ChecksDisciplinePermissions, HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->allowsAny($user, 'ViewAny:Category') || $this->allowsAny($user, 'EditField');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->allowsFor($user, 'View:Category', $category->discipline_id) || $this->allowsFor($user, 'EditField', $category->discipline_id);
    }

    public function create(User $user): bool
    {
        return $this->allowsAny($user, 'Create:Category') || $this->allowsAny($user, 'EditField');
    }

    public function update(User $user, Category $category): bool
    {
        return $this->allowsFor($user, 'Update:Category', $category->discipline_id) || $this->allowsFor($user, 'EditField', $category->discipline_id);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->allowsFor($user, 'Delete:Category', $category->discipline_id) || $this->allowsFor($user, 'EditField', $category->discipline_id);
    }
}
