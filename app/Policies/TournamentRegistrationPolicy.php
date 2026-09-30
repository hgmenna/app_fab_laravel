<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TournamentRegistration;
use App\Models\User;
use App\Policies\Concerns\ChecksDisciplinePermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class TournamentRegistrationPolicy
{
    use ChecksDisciplinePermissions, HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->allowsAny($user, 'ViewAny:TournamentRegistration');
    }

    public function view(User $user, TournamentRegistration $registration): bool
    {
        return $this->allowsFor($user, 'View:TournamentRegistration', $registration->tournament?->discipline_id);
    }

    public function create(User $user): bool
    {
        return $this->allowsAny($user, 'Create:TournamentRegistration');
    }

    public function update(User $user, TournamentRegistration $registration): bool
    {
        return $this->allowsFor($user, 'Update:TournamentRegistration', $registration->tournament?->discipline_id);
    }

    public function delete(User $user, TournamentRegistration $registration): bool
    {
        return $this->allowsFor($user, 'Delete:TournamentRegistration', $registration->tournament?->discipline_id);
    }

    public function restore(User $user, TournamentRegistration $registration): bool
    {
        return $this->allowsFor($user, 'Restore:TournamentRegistration', $registration->tournament?->discipline_id);
    }

    public function forceDelete(User $user, TournamentRegistration $registration): bool
    {
        return $this->allowsFor($user, 'ForceDelete:TournamentRegistration', $registration->tournament?->discipline_id);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->allowsAny($user, 'ForceDeleteAny:TournamentRegistration');
    }

    public function restoreAny(User $user): bool
    {
        return $this->allowsAny($user, 'RestoreAny:TournamentRegistration');
    }

    public function replicate(User $user, TournamentRegistration $registration): bool
    {
        return $this->allowsFor($user, 'Replicate:TournamentRegistration', $registration->tournament?->discipline_id);
    }

    public function reorder(User $user): bool
    {
        return $this->allowsAny($user, 'Reorder:TournamentRegistration');
    }
}
