<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use CanResetPassword, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $allRoles = Role::pluck('name')->toArray();

        // Verificamos si el usuario tiene cualquiera de esos roles
        if ($this->hasAnyRole($allRoles)) {
            return true;
        }

        return collect([
            'ViewAny:Discipline',
            'ViewAny:Tournament',
            'ViewAny:TournamentType',
            'ViewAny:Player',
            'ViewAny:Category',
            'ViewAny:DisciplineModality',
        ])->contains(fn (string $permission): bool => $this->hasPermissionInAnyDiscipline($permission));
    }

    public function disciplineAssignments()
    {
        return $this->hasMany(DisciplineUserRole::class);
    }

    public function hasActiveDisciplineAssignments(): bool
    {
        return $this->disciplineAssignments()->where('is_active', true)->exists();
    }

    public function allowedDisciplineIds(?string $permission = null): array
    {
        return $this->disciplineAssignments()
            ->where('is_active', true)
            ->when($permission, fn ($query) => $query->whereHas(
                'role.permissions',
                fn ($permissions) => $permissions->where('name', $permission),
            ))
            ->pluck('discipline_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function hasPermissionInAnyDiscipline(string $permission): bool
    {
        return $this->disciplineAssignments()
            ->where('is_active', true)
            ->whereHas('role.permissions', fn ($permissions) => $permissions->where('name', $permission))
            ->exists();
    }

    public function canGloballyOrInAnyDiscipline(string $permission): bool
    {
        return $this->hasActiveDisciplineAssignments()
            ? $this->hasPermissionInAnyDiscipline($permission)
            : $this->can($permission);
    }

    public function canGloballyOrForDiscipline(string $permission, int|string|null $disciplineId): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        return $this->hasActiveDisciplineAssignments()
            ? $this->hasDisciplinePermission($permission, $disciplineId)
            : $this->can($permission);
    }

    public function hasDisciplinePermission(string $permission, int|string|null $disciplineId): bool
    {
        return filled($disciplineId) && $this->disciplineAssignments()
            ->where('is_active', true)
            ->where('discipline_id', $disciplineId)
            ->whereHas('role.permissions', fn ($permissions) => $permissions->where('name', $permission))
            ->exists();
    }

    public function defaultDisciplineId(?string $permission = null): ?int
    {
        $ids = $this->allowedDisciplineIds($permission);

        return count($ids) === 1 ? $ids[0] : null;
    }

    public function allowedClubIds(string $permission, int|string|null $disciplineId = null): array
    {
        return $this->disciplineAssignments()
            ->where('is_active', true)
            ->whereNotNull('club_id')
            ->when(filled($disciplineId), fn ($query) => $query->where('discipline_id', $disciplineId))
            ->whereHas('role.permissions', fn ($permissions) => $permissions->where('name', $permission))
            ->pluck('club_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function hasClubScopeForDiscipline(int|string|null $disciplineId, string $permission): bool
    {
        if (! filled($disciplineId) || $this->hasRole('super-admin') || ! $this->hasActiveDisciplineAssignments()) {
            return false;
        }

        $assignment = $this->disciplineAssignments()
            ->where('is_active', true)
            ->where('discipline_id', $disciplineId)
            ->whereHas('role.permissions', fn ($permissions) => $permissions->where('name', $permission))
            ->first();

        return $assignment !== null && filled($assignment->club_id);
    }

    public function canAccessClubInDiscipline(
        string $permission,
        int|string|null $disciplineId,
        int|string|null $clubId,
    ): bool {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        if (! $this->hasActiveDisciplineAssignments()) {
            return $this->can($permission);
        }

        if (! filled($disciplineId)) {
            return false;
        }

        return $this->disciplineAssignments()
            ->where('is_active', true)
            ->where('discipline_id', $disciplineId)
            ->whereHas('role.permissions', fn ($permissions) => $permissions->where('name', $permission))
            ->where(function ($query) use ($clubId): void {
                $query->whereNull('club_id');

                if (filled($clubId)) {
                    $query->orWhere('club_id', $clubId);
                }
            })
            ->exists();
    }

    public function defaultClubId(string $permission, int|string|null $disciplineId = null): ?int
    {
        $ids = $this->allowedClubIds($permission, $disciplineId);

        return count($ids) === 1 ? $ids[0] : null;
    }

    public function canAccessTournament(string $permission, ?Tournament $tournament): bool
    {
        if (! $tournament) {
            return false;
        }

        return $this->canAccessClubInDiscipline(
            $permission,
            $tournament->discipline_id,
            $tournament->venue_type === 'club' ? $tournament->venue_id : null,
        );
    }

    public function scopeDisciplineQuery($query, string $permission)
    {
        if ($this->hasRole('super-admin') || ! $this->hasActiveDisciplineAssignments()) {
            return $query;
        }

        return $query->whereKey($this->allowedDisciplineIds($permission) ?: [-1]);
    }
}
