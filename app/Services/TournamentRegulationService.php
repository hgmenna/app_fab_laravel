<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Tournament;
use App\Models\TournamentRegulationSetting;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class TournamentRegulationService
{
    public function __construct(private readonly GoogleMapsRouteService $routes) {}

    public function evaluate(Tournament $candidate): TournamentRegulationEvaluation
    {
        $candidate->loadMissing(['type', 'venue.city.state.country', 'discipline']);

        $setting = TournamentRegulationSetting::query()
            ->where('discipline_id', $candidate->discipline_id)
            ->where('enabled', true)
            ->first();

        if ($setting && ! $this->settingApplies($setting, $candidate->start_date)) {
            $setting = null;
        }

        $candidateStart = $candidate->start_date;
        $candidateEnd = $candidate->end_date ?: $candidateStart;

        $existingTournaments = Tournament::query()
            ->with(['type', 'venue.city.state.country'])
            ->where('discipline_id', $candidate->discipline_id)
            ->when($candidate->getKey(), fn (Builder $query, $id) => $query->where('id', '!=', $id))
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereDate('start_date', '<=', $candidateEnd)
            ->where(function (Builder $query) use ($candidateStart): void {
                $query->whereDate('end_date', '>=', $candidateStart)
                    ->orWhere(function (Builder $withoutEnd) use ($candidateStart): void {
                        $withoutEnd->whereNull('end_date')->whereDate('start_date', '>=', $candidateStart);
                    });
            })
            ->get();

        $conflicts = [];

        foreach ($existingTournaments as $existing) {
            $sharedCategoryIds = $this->sharedCategoryIds($candidate, $existing);
            $sharedCategoryNames = Category::query()->whereKey($sharedCategoryIds)->pluck('name')->values()->all();

            if ($candidate->type?->exclusive_during_dates || $existing->type?->exclusive_during_dates) {
                $conflicts[] = $this->conflict(
                    'exclusive_dates',
                    $existing,
                    $sharedCategoryNames,
                    'Existe un tipo de torneo exclusivo durante esas fechas.'
                );

                continue;
            }

            if ($sharedCategoryIds === []) {
                continue;
            }

            $candidateOfficial = (bool) $candidate->type?->is_official;
            $existingOfficial = (bool) $existing->type?->is_official;

            if ($setting?->block_non_official_against_official_same_state
                && $candidateOfficial !== $existingOfficial
                && $candidate->venue?->city?->state_id
                && $candidate->venue?->city?->state_id === $existing->venue?->city?->state_id) {
                $conflicts[] = $this->conflict(
                    'official_same_state',
                    $existing,
                    $sharedCategoryNames,
                    'Un torneo oficial y uno no oficial no pueden compartir fechas, provincia y categorías.'
                );

                continue;
            }

            if (! $setting?->check_non_official_distance || $candidateOfficial || $existingOfficial) {
                continue;
            }

            try {
                $route = $this->routes->distanceInMeters($candidate->venue, $existing->venue);
                $minimumMeters = (int) round(((float) $setting->minimum_distance_km) * 1000);

                if ($route['distance_meters'] < $minimumMeters) {
                    $distance = number_format($route['distance_meters'] / 1000, 1, ',', '.');
                    $minimum = number_format((float) $setting->minimum_distance_km, 1, ',', '.');
                    $conflicts[] = $this->conflict(
                        'minimum_distance',
                        $existing,
                        $sharedCategoryNames,
                        "La distancia entre los clubes es {$distance} km y el mínimo configurado es {$minimum} km.",
                        $route + ['minimum_distance_meters' => $minimumMeters]
                    );
                }
            } catch (RuntimeException $exception) {
                $conflicts[] = $this->conflict(
                    'distance_unavailable',
                    $existing,
                    $sharedCategoryNames,
                    'No se pudo verificar la distancia reglamentaria: '.$exception->getMessage()
                );
            }
        }

        return new TournamentRegulationEvaluation($conflicts, [
            'setting_id' => $setting?->id,
            'evaluated_at' => now()->toIso8601String(),
            'compared_tournaments' => $existingTournaments->pluck('id')->all(),
        ]);
    }

    private function settingApplies(TournamentRegulationSetting $setting, ?CarbonInterface $date): bool
    {
        if (! $date) {
            return false;
        }

        return (! $setting->effective_from || $date->gte($setting->effective_from))
            && (! $setting->effective_until || $date->lte($setting->effective_until));
    }

    private function sharedCategoryIds(Tournament $first, Tournament $second): array
    {
        return collect($first->categories ?? [])->map(fn ($id): int => (int) $id)
            ->intersect(collect($second->categories ?? [])->map(fn ($id): int => (int) $id))
            ->unique()->values()->all();
    }

    private function conflict(
        string $rule,
        Tournament $existing,
        array $categories,
        string $reason,
        array $route = [],
    ): array {
        $categoryText = $categories === [] ? 'sin considerar categorías' : implode(', ', $categories);

        return [
            'rule' => $rule,
            'conflicting_tournament_id' => $existing->id,
            'conflicting_tournament' => $existing->name,
            'club' => $existing->venue?->name,
            'start_date' => $existing->start_date?->format('d/m/Y'),
            'end_date' => ($existing->end_date ?: $existing->start_date)?->format('d/m/Y'),
            'shared_categories' => $categories,
            'route' => $route,
            'message' => "Conflicto con «{$existing->name}» ({$categoryText}): {$reason}",
        ];
    }
}
