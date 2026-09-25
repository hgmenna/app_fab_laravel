<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Tournament;
use App\Models\TournamentRegulationSetting;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

class TournamentRegulationService
{
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
            ->where('status', '!=', 'cancelled')
            ->whereDate('start_date', '<=', $candidateEnd)
            ->where(function (Builder $query) use ($candidateStart): void {
                $query->whereDate('end_date', '>=', $candidateStart)
                    ->orWhere(function (Builder $withoutEnd) use ($candidateStart): void {
                        $withoutEnd->whereNull('end_date')->whereDate('start_date', '>=', $candidateStart);
                    });
            })
            ->get();

        $conflicts = [];
        $distanceChecks = [];

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

            $minimumMeters = (int) round(((float) $setting->minimum_distance_km) * 1000);
            $route = $this->manualRouteData($candidate, $existing) + [
                'minimum_distance_meters' => $minimumMeters,
            ];
            $distanceChecks[] = $route;

            if (! $route['is_complete']) {
                $reason = ! $route['addresses_complete']
                    ? 'Uno de los clubes no tiene dirección postal, localidad y provincia completas.'
                    : 'Abrí la ruta de Google Maps y completá la distancia junto con el comprobante en la pestaña «Verificación de distancias».';
                $conflicts[] = $this->conflict(
                    'manual_distance_required',
                    $existing,
                    $sharedCategoryNames,
                    $reason,
                    $route
                );

                continue;
            }

            if ($route['distance_meters'] < $minimumMeters) {
                $distance = number_format($route['distance_meters'] / 1000, 1, ',', '.');
                $minimum = number_format((float) $setting->minimum_distance_km, 1, ',', '.');
                $conflicts[] = $this->conflict(
                    'minimum_distance',
                    $existing,
                    $sharedCategoryNames,
                    "La distancia informada es {$distance} km y el mínimo configurado es {$minimum} km.",
                    $route
                );
            }
        }

        [$quotaConflicts, $quotaChecks, $quotaTournamentIds] = $this->clubCategoryQuotaConflicts(
            $candidate,
            $setting,
        );
        $conflicts = [...$conflicts, ...$quotaConflicts];

        return new TournamentRegulationEvaluation($conflicts, [
            'setting_id' => $setting?->id,
            'evaluated_at' => now()->toIso8601String(),
            'compared_tournaments' => $existingTournaments->pluck('id')
                ->merge($quotaTournamentIds)
                ->unique()
                ->values()
                ->all(),
            'distance_checks' => $distanceChecks,
            'club_category_quota_checks' => $quotaChecks,
        ]);
    }

    private function clubCategoryQuotaConflicts(
        Tournament $candidate,
        ?TournamentRegulationSetting $setting,
    ): array {
        $maximum = (int) ($setting?->max_non_official_tournaments_per_category ?? 0);
        $periodMonths = (int) ($setting?->club_category_period_months ?? 0);

        if (! $setting?->check_club_category_quota
            || $maximum < 1
            || $periodMonths < 1
            || ! $candidate->start_date
            || ! $candidate->venue_id
            || (bool) $candidate->type?->is_official) {
            return [[], [], []];
        }

        $candidateStart = $candidate->start_date->copy()->startOfDay();
        $rangeStart = $candidateStart->copy()->subMonthsNoOverflow($periodMonths);
        $rangeEnd = $candidateStart->copy()->addMonthsNoOverflow($periodMonths);

        $clubTournaments = Tournament::query()
            ->with('type')
            ->where('discipline_id', $candidate->discipline_id)
            ->where('venue_id', $candidate->venue_id)
            ->when($candidate->getKey(), fn (Builder $query, $id) => $query->where('id', '!=', $id))
            ->where('status', '!=', 'cancelled')
            ->whereHas('type', fn (Builder $query) => $query->where('is_official', false))
            ->where(function (Builder $query): void {
                $query->whereDate('end_date', '>=', today('America/Argentina/Buenos_Aires'))
                    ->orWhere(function (Builder $withoutEnd): void {
                        $withoutEnd->whereNull('end_date')
                            ->whereDate('start_date', '>=', today('America/Argentina/Buenos_Aires'));
                    });
            })
            ->whereBetween('start_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->orderBy('start_date')
            ->get();

        $categoryIds = collect($candidate->categories ?? [])->map(fn ($id): int => (int) $id)->unique();
        $categoryNames = Category::query()->whereKey($categoryIds)->pluck('name', 'id');
        $conflicts = [];
        $checks = [];

        foreach ($categoryIds as $categoryId) {
            $matching = $clubTournaments
                ->filter(fn (Tournament $tournament): bool => collect($tournament->categories ?? [])
                    ->map(fn ($id): int => (int) $id)
                    ->contains($categoryId))
                ->values();

            $windowStarts = $matching
                ->pluck('start_date')
                ->filter(fn ($date): bool => $date->lte($candidateStart))
                ->push($candidateStart)
                ->unique(fn ($date): string => $date->toDateString())
                ->sort()
                ->values();

            $worstWindow = null;

            foreach ($windowStarts as $windowStart) {
                $windowEnd = $windowStart->copy()->addMonthsNoOverflow($periodMonths);

                if ($candidateStart->lt($windowStart) || $candidateStart->gt($windowEnd)) {
                    continue;
                }

                $tournamentsInWindow = $matching
                    ->filter(fn (Tournament $tournament): bool => $tournament->start_date->betweenIncluded(
                        $windowStart,
                        $windowEnd,
                    ))
                    ->values();
                $totalWithCandidate = $tournamentsInWindow->count() + 1;

                if ($worstWindow === null || $totalWithCandidate > $worstWindow['total_with_candidate']) {
                    $worstWindow = [
                        'start' => $windowStart,
                        'end' => $windowEnd,
                        'tournaments' => $tournamentsInWindow,
                        'total_with_candidate' => $totalWithCandidate,
                    ];
                }
            }

            if ($worstWindow === null) {
                continue;
            }

            $categoryName = $categoryNames->get($categoryId, "Categoría {$categoryId}");
            $relatedTournaments = $worstWindow['tournaments']
                ->map(fn (Tournament $tournament): array => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'start_date' => $tournament->start_date?->format('d/m/Y'),
                ])
                ->all();
            $check = [
                'category_id' => $categoryId,
                'category' => $categoryName,
                'maximum_allowed' => $maximum,
                'period_months' => $periodMonths,
                'period_start' => $worstWindow['start']->format('d/m/Y'),
                'period_end' => $worstWindow['end']->format('d/m/Y'),
                'active_tournaments' => count($relatedTournaments),
                'total_with_candidate' => $worstWindow['total_with_candidate'],
                'related_tournaments' => $relatedTournaments,
            ];
            $checks[] = $check;

            if ($worstWindow['total_with_candidate'] <= $maximum) {
                continue;
            }

            $conflicts[] = [
                'rule' => 'club_category_quota',
                'conflicting_tournament_id' => data_get($relatedTournaments, '0.id'),
                'conflicting_tournament' => 'Cupo del club para '.$categoryName,
                'club' => $candidate->venue?->name,
                'province' => $candidate->venue?->city?->state?->name,
                'start_date' => $worstWindow['start']->format('d/m/Y'),
                'end_date' => $worstWindow['end']->format('d/m/Y'),
                'shared_categories' => [$categoryName],
                'route' => [],
                'quota' => $check,
                'message' => "El club supera el máximo de {$maximum} torneos no oficiales activos de {$categoryName} permitido durante {$periodMonths} meses. Debe finalizar uno de los torneos contabilizados antes de solicitar otro.",
            ];
        }

        return [
            $conflicts,
            $checks,
            $clubTournaments->pluck('id')->all(),
        ];
    }

    public function googleMapsUrl(Tournament $candidate, Tournament $existing): string
    {
        $candidate->loadMissing('venue.city.state.country');
        $existing->loadMissing('venue.city.state.country');

        return $this->googleMapsUrlForClubs($candidate->venue, $existing->venue);
    }

    public function googleMapsUrlForClubs($origin, $destination): string
    {
        return 'https://www.google.com/maps/dir/?'.http_build_query([
            'api' => 1,
            'origin' => $this->fullAddress($origin),
            'destination' => $this->fullAddress($destination),
            'travelmode' => 'driving',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function manualRouteData(Tournament $candidate, Tournament $existing): array
    {
        $check = collect($candidate->manual_route_checks ?? [])->first(
            fn (array $item): bool => (int) ($item['conflicting_tournament_id'] ?? 0) === (int) $existing->id
        );
        $distanceKm = is_numeric($check['distance_km'] ?? null) ? (float) $check['distance_km'] : null;
        $evidence = trim((string) ($check['evidence_path'] ?? ''));
        $addressesComplete = $this->hasCompleteAddress($candidate->venue)
            && $this->hasCompleteAddress($existing->venue);

        return [
            'verification_method' => 'Google Maps URL con carga manual',
            'google_maps_url' => $this->googleMapsUrl($candidate, $existing),
            'origin_address' => $this->fullAddress($candidate->venue),
            'destination_address' => $this->fullAddress($existing->venue),
            'distance_meters' => $distanceKm === null ? null : (int) round($distanceKm * 1000),
            'distance_km' => $distanceKm,
            'evidence_path' => $evidence ?: null,
            'addresses_complete' => $addressesComplete,
            'checked_by' => $check['checked_by'] ?? null,
            'checked_at' => $check['checked_at'] ?? null,
            'is_complete' => $addressesComplete && $distanceKm !== null && $distanceKm >= 0 && $evidence !== '',
        ];
    }

    public function fullAddress($club): string
    {
        $club?->loadMissing('city.state.country');

        return implode(', ', array_filter([
            trim((string) $club?->address),
            $club?->city?->name,
            $club?->city?->state?->name,
            $club?->city?->state?->country?->name ?: 'Argentina',
        ]));
    }

    private function hasCompleteAddress($club): bool
    {
        $club?->loadMissing('city.state');

        return trim((string) $club?->address) !== ''
            && trim((string) $club?->city?->name) !== ''
            && trim((string) $club?->city?->state?->name) !== '';
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
            'province' => $existing->venue?->city?->state?->name,
            'start_date' => $existing->start_date?->format('d/m/Y'),
            'end_date' => ($existing->end_date ?: $existing->start_date)?->format('d/m/Y'),
            'shared_categories' => $categories,
            'route' => $route,
            'message' => "Conflicto con «{$existing->name}» ({$categoryText}): {$reason}",
        ];
    }
}
