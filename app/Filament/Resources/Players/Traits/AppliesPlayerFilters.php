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
            $categoryIds = $filters['category_id']['values'] ?? $filters['category_id'];
            $query->whereIn('category_id', array_filter((array) $categoryIds));
        }

        if (!empty($filters['discipline_id'])) {
            $disciplineValues = $filters['discipline_id']['values'] ?? $filters['discipline_id'];
            $query->whereIn('discipline_id', array_filter((array) $disciplineValues));
        }

        // Filtro por federación
        if (!empty($filters['federation_id'])) {
            $federationValues = $filters['federation_id']['values'] ?? $filters['federation_id'];
            $federationIds = array_values(array_filter((array) $federationValues));

            $query->where(function (Builder $affiliations) use ($federationIds): void {
                $affiliations
                    ->whereHas('discipline', fn (Builder $disciplines) => $disciplines
                        ->where('affiliation_mode', 'direct')
                        ->whereIn('direct_federation_id', $federationIds))
                    ->orWhere(function (Builder $provincial) use ($federationIds): void {
                        $provincial
                            ->whereHas('discipline', fn (Builder $disciplines) => $disciplines
                                ->where('affiliation_mode', 'provincial'))
                            ->whereHas('club.city.state', fn (Builder $state) => $state
                                ->whereIn('federation_id', $federationIds));
                    });
            });
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
                  )
                  ->orWhereHas('discipline', fn($q) =>
                      $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('directFederation', fn ($federation) =>
                            $federation->where('short_name', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                        )
                  );
            });
        }

        return $query;
    }

}

