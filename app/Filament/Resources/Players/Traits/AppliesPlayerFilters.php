<?php

namespace App\Filament\Resources\Players\Traits;

use Illuminate\Database\Eloquent\Builder;

trait AppliesPlayerFilters
{
    /**
     * Aplica los filtros de la tabla del recurso Players
     * a cualquier query usada en widgets (stats, charts, etc.)
     */
    public function applyPlayersFilters(Builder $query, array $filters, ?string $search = null): Builder
    {
        // Filtro por categoría
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filtro por federación
        if (!empty($filters['federation_id'])) {
            $query->whereHas('club.city.state.federation', fn ($q) =>
                $q->where('id', $filters['federation_id'])
            );
        }

        // Trashed
        if (!empty($filters['trashed'])) {
            if ($filters['trashed'] === 'only') $query->onlyTrashed();
            if ($filters['trashed'] === 'with') $query->withTrashed();
        }

        // Búsqueda
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('last_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhereHas('club', fn($q) =>
                      $q->where('name', 'like', "%{$search}%")
                  )
                  ->orWhereHas('category', fn($q) =>
                      $q->where('code', 'like', "%{$search}%")
                  )
                  ->orWhereHas('club.city.state.federation', fn($q) =>
                      $q->where('short_name', 'like', "%{$search}%")
                  );
            });
        }

        return $query;
    }

}

