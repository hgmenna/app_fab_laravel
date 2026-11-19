<?php

namespace App\Policies;

use App\Models\PlayerCategoryHistory;
use App\Models\User;
use App\Policies\Concerns\ChecksDisciplinePermissions;

/** Los cambios usan los permisos de jugadores, acotados a disciplina y club, y la habilitación EditField. */
class PlayerCategoryHistoryPolicy
{
    use ChecksDisciplinePermissions;

    public function viewAny(User $user): bool { return $this->allowsAny($user, 'ViewAny:Player'); }
    public function view(User $user, PlayerCategoryHistory $history): bool
    {
        return $history->player && $user->can('view', $history->player);
    }
    public function create(User $user): bool
    {
        return $this->allowsAny($user, 'EditField') && $this->allowsAny($user, 'Update:Player');
    }
    public function update(User $user, PlayerCategoryHistory $history): bool
    {
        return $this->forPlayer($user, $history, 'Update:Player');
    }
    public function delete(User $user, PlayerCategoryHistory $history): bool
    {
        return $this->forPlayer($user, $history, 'Delete:Player');
    }
    private function forPlayer(User $user, PlayerCategoryHistory $history, string $permission): bool
    {
        $player = $history->player;
        return $player && in_array($history->source, ['manual', 'season_promotion'], true)
            && $user->canAccessClubInDiscipline('EditField', $player->discipline_id, $player->club_id)
            && $user->can($permission === 'Update:Player' ? 'update' : 'delete', $player);
    }
}
