<?php

namespace App\Policies;

use App\Models\DisciplineModality;
use App\Models\User;
use App\Policies\Concerns\ChecksDisciplinePermissions;

class DisciplineModalityPolicy
{
    use ChecksDisciplinePermissions;

    public function viewAny(User $user): bool
    {
        return $this->allowsAny($user, 'ViewAny:DisciplineModality') || $this->allowsAny($user, 'EditField');
    }

    public function view(User $user, DisciplineModality $record): bool
    {
        return $this->allowsFor($user, 'View:DisciplineModality', $record->discipline_id) || $this->allowsFor($user, 'EditField', $record->discipline_id);
    }

    public function create(User $user): bool
    {
        return $this->allowsAny($user, 'Create:DisciplineModality') || $this->allowsAny($user, 'EditField');
    }

    public function update(User $user, DisciplineModality $record): bool
    {
        return $this->allowsFor($user, 'Update:DisciplineModality', $record->discipline_id) || $this->allowsFor($user, 'EditField', $record->discipline_id);
    }

    public function delete(User $user, DisciplineModality $record): bool
    {
        return $this->allowsFor($user, 'Delete:DisciplineModality', $record->discipline_id) || $this->allowsFor($user, 'EditField', $record->discipline_id);
    }
}
