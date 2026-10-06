<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Discipline;
use App\Models\ThreeCushionRanking;
use App\Models\ThreeCushionStageResult;
use App\Models\Tournament;
use App\Models\TournamentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ThreeCushionRankingService
{
    public static function isThreeCushion(?Discipline $discipline): bool
    {
        if (! $discipline) {
            return false;
        }

        $value = Str::of($discipline->code ?: $discipline->name)
            ->ascii()->lower()->replace(['_', '-'], ' ')->squish()->toString();

        return str_contains($value, '3 banda') || str_contains($value, 'three cushion');
    }

    public function discipline(): ?Discipline
    {
        return Discipline::query()->get()->first(fn (Discipline $discipline): bool => self::isThreeCushion($discipline));
    }

    public function syncForResult(ThreeCushionStageResult $result): void
    {
        if (! Schema::hasTable('three_cushion_rankings')) {
            return;
        }

        $registration = $result->registration()->with('tournament')->first();
        $season = $registration?->tournament?->end_date?->year;

        if ($season) {
            $this->syncSeason((int) $season, (int) $registration->tournament->discipline_id);
        }
    }

    public function syncForTournamentType(TournamentType $type): void
    {
        if (! Schema::hasTable('three_cushion_stage_results') || ! self::isThreeCushion($type->discipline)) {
            return;
        }

        $seasons = $type->tournaments()->whereNotNull('end_date')
            ->selectRaw('YEAR(end_date) as season')->distinct()->pluck('season');

        foreach ($seasons as $season) {
            $this->syncSeason((int) $season, (int) $type->discipline_id);
        }
    }

    public function syncSeason(int $season, ?int $disciplineId = null): Collection
    {
        $discipline = $disciplineId
            ? Discipline::query()->find($disciplineId)
            : $this->discipline();

        if (! self::isThreeCushion($discipline)) {
            return collect();
        }

        $tournaments = Tournament::query()
            ->with(['type', 'tournamentModalities'])
            ->where('discipline_id', $discipline->id)
            ->whereYear('end_date', $season)
            ->whereHas('type', fn ($query) => $query->where('affects_ranking', true))
            ->get();

        $results = ThreeCushionStageResult::query()
            ->with(['registration.tournament', 'registration', 'player'])
            ->whereHas('registration', function ($query) use ($tournaments): void {
                $query->where('status', 'aprobado')->whereIn('tournament_id', $tournaments->modelKeys());
            })
            ->get();

        $rows = collect();

        foreach ($results->groupBy(['category_id', 'player_id']) as $categoryId => $players) {
            $categoryRows = collect();

            foreach ($players as $playerId => $playerResults) {
                $totalCaroms = (int) $playerResults->sum('caroms');
                $totalInnings = (int) $playerResults->sum('innings');
                $highRun = (int) $playerResults->max('high_run');
                $oldestHighRunDate = $playerResults
                    ->where('high_run', $highRun)
                    ->pluck('high_run_achieved_at')
                    ->filter()
                    ->sort()
                    ->first();

                $categoryRows->push([
                    'season' => $season,
                    'category_id' => (int) $categoryId,
                    'player_id' => (int) $playerId,
                    'total_caroms' => $totalCaroms,
                    'total_innings' => $totalInnings,
                    'high_run' => $highRun,
                    'high_run_achieved_at' => $oldestHighRunDate,
                    'general_average' => $totalInnings > 0 ? round($totalCaroms / $totalInnings, 6) : 0,
                    'best_match_average' => (float) $playerResults->max('best_match_average'),
                    'ranking_points' => round($playerResults->sum(
                        fn (ThreeCushionStageResult $result): float => (float) $result->registration?->points
                    ), 2),
                    'stages_played' => $playerResults->pluck('registration.tournament_id')->unique()->count(),
                    'total_stages' => $this->totalStagesForCategory($tournaments, (int) $categoryId),
                    'player_name' => $playerResults->first()?->player?->full_name ?? '',
                ]);
            }

            $categoryRows = $categoryRows->sort(function (array $left, array $right): int {
                foreach (['ranking_points', 'general_average', 'best_match_average', 'high_run'] as $field) {
                    $comparison = $right[$field] <=> $left[$field];
                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                $dateComparison = ($left['high_run_achieved_at'] ?? '9999-12-31')
                    <=> ($right['high_run_achieved_at'] ?? '9999-12-31');

                return $dateComparison !== 0
                    ? $dateComparison
                    : strcasecmp($left['player_name'], $right['player_name']);
            })->values();

            foreach ($categoryRows as $index => $row) {
                unset($row['player_name']);
                $row['position'] = $index + 1;
                $rows->push($row);
            }
        }

        DB::transaction(function () use ($season, $discipline, $rows): void {
            $categoryIds = Category::query()->where('discipline_id', $discipline->id)->pluck('id');
            ThreeCushionRanking::query()
                ->where('season', $season)
                ->whereIn('category_id', $categoryIds)
                ->delete();

            foreach ($rows as $row) {
                ThreeCushionRanking::query()->create($row);
            }
        });

        return $rows;
    }

    private function totalStagesForCategory(Collection $tournaments, int $categoryId): int
    {
        return $tournaments->filter(function (Tournament $tournament) use ($categoryId): bool {
            $categoryIds = collect($tournament->categories ?? [])
                ->merge($tournament->tournamentModalities->flatMap(fn ($modality) => $modality->categories ?? []))
                ->map(fn ($id): int => (int) $id)
                ->unique();

            return $categoryIds->isEmpty() || $categoryIds->contains($categoryId);
        })->count();
    }
}
