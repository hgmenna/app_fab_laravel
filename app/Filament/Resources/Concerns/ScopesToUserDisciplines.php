<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait ScopesToUserDisciplines
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->hasRole('super-admin') || ! $user->hasActiveDisciplineAssignments()) {
            return $query;
        }

        return static::applyDisciplineScope(
            $query,
            $user->allowedDisciplineIds(static::disciplineViewPermission()),
        );
    }

    protected static function applyDisciplineScope(Builder $query, array $disciplineIds): Builder
    {
        return $query->whereIn($query->qualifyColumn('discipline_id'), $disciplineIds ?: [-1]);
    }

    protected static function disciplineViewPermission(): string
    {
        return 'ViewAny:'.class_basename(static::getModel());
    }
}
