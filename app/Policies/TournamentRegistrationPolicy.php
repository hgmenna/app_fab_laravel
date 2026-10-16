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
        return $this->allowsRegistration($user, 'View:TournamentRegistration', $registration);
    }

    public function create(User $user): bool
    {
        return $this->allowsAny($user, 'Create:TournamentRegistration');
    }

    public function update(User $user, TournamentRegistration $registration): bool
    {
        return $this->allowsRegistration($user, 'Update:TournamentRegistration', $registration);
    }

    public function delete(User $user, TournamentRegistration $registration): bool
    {
        return $this->allowsRegistration($user, 'Delete:TournamentRegistration', $registration);
    }

    public function restore(User $user, TournamentRegistration $registration): bool
    {
        return $this->allowsRegistration($user, 'Restore:TournamentRegistration', $registration);
    }

    public function forceDelete(User $user, TournamentRegistration $registration): bool
    {
        return $this->allowsRegistration($user, 'ForceDelete:TournamentRegistration', $registration);
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
        return $this->allowsRegistration($user, 'Replicate:TournamentRegistration', $registration);
    }

    public function reorder(User $user): bool
    {
        return $this->allowsAny($user, 'Reorder:TournamentRegistration');
    }

    private function allowsRegistration(User $user, string $permission, TournamentRegistration $registration): bool
    {
        $tournament = $registration->tournament;

        return $user->canAccessClubInDiscipline(
            $permission,
            $tournament?->discipline_id,
            $tournament?->organizer_club_id,
        );
    }
}
