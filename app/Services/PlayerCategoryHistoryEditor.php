<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Player;
use App\Models\PlayerCategoryHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Corrige cambios permanentes y recompone la categoría actual sin modificar torneos o rankings históricos. */
class PlayerCategoryHistoryEditor
{
    public function create(Player $player, array $data): PlayerCategoryHistory
    {
        Gate::authorize('create', PlayerCategoryHistory::class);
        $this->authorizePlayer($player, 'Update:Player');
        $data = $this->validated($player, $data);
        try {
            return PlayerCategoryChangeService::scheduleManualChange($player, Category::findOrFail($data['category_id']), $data['effective_date'], $data['reason'], $data['notes'] ?? null);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['category_id' => $exception->getMessage()]);
        }
    }

    public function update(PlayerCategoryHistory $history, array $data): PlayerCategoryHistory
    {
        return $this->mutate($history, function ($locked, $player) use ($data) {
            Gate::authorize('update', $locked);
            $values = $this->validated($player, $data);
            $locked->fill($values + ['season' => (int) substr($values['effective_date'], 0, 4)])->save();
        })->refresh();
    }

    public function delete(PlayerCategoryHistory $history): void
    {
        $this->mutate($history, function ($locked) { $locked->delete(); }, 'delete');
    }

    /** Bloquea al jugador y todos sus cambios para corregir también cadenas posteriores de categorías. */
    private function mutate(PlayerCategoryHistory $history, callable $change, string $ability = 'update'): PlayerCategoryHistory
    {
        Gate::authorize($ability, $history);
        return DB::transaction(function () use ($history, $change, $ability) {
            $player = Player::query()->lockForUpdate()->findOrFail($history->player_id);
            $histories = $this->histories($player)->lockForUpdate()->get();
            $locked = $histories->firstWhere('id', $history->id);
            if (! $locked) throw ValidationException::withMessages(['category_id' => 'Solo se pueden corregir cambios permanentes manuales o de temporada.']);
            $locked->setRelation('player', $player);
            Gate::authorize($ability, $locked);
            $base = $histories->first()->previous_category_id;
            $lastApplied = $histories->whereNotNull('applied_at')->sortBy(fn($row) => [$row->applied_at->timestamp, $row->id])->last();
            $chronological = $histories->whereNotNull('applied_at')->last();
            if (! $base || ! in_array((int)$player->category_id, [(int)($lastApplied?->category_id ?? $base), (int)($chronological?->category_id ?? $base)], true)) {
                throw ValidationException::withMessages(['category_id' => 'La categoría actual fue modificada fuera de esta cadena. Revisá el historial antes de corregirla.']);
            }
            $change($locked, $player);
            $projected = $current = (int)$base;
            foreach ($this->histories($player)->get() as $row) {
                $applied = $row->effective_date && $row->effective_date->lte(today());
                $row->update(['previous_category_id' => $projected, 'applied_at' => $applied ? ($row->applied_at ?? now()) : null]);
                $projected = (int)$row->category_id;
                if ($applied) $current = $projected;
            }
            $player->category_id = $current;
            $player->save();
            return $locked;
        });
    }

    private function histories(Player $player)
    {
        return PlayerCategoryHistory::query()->where('player_id', $player->id)->whereIn('source', ['manual', 'season_promotion'])->orderBy('effective_date')->orderBy('id');
    }

    private function authorizePlayer(Player $player, string $permission): void
    {
        Gate::authorize('update', $player);
        $user = auth()->user();
        abort_unless($user && $user->canAccessClubInDiscipline('EditField', $player->discipline_id, $player->club_id)
            && $user->canAccessClubInDiscipline($permission, $player->discipline_id, $player->club_id), 403);
    }

    private function validated(Player $player, array $data): array
    {
        $data = Validator::make($data, [
            'category_id' => ['required', 'integer'], 'effective_date' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'max:500'], 'notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();
        if (! Category::query()->whereKey($data['category_id'])->where('discipline_id', $player->discipline_id)
            ->whereNotIn('code', config('ranking.temporary_ranking_categories', ['M', 'N']))->exists()) {
            throw ValidationException::withMessages(['category_id' => 'Elegí una categoría permanente de la disciplina del jugador.']);
        }
        $data['reason'] = trim($data['reason']);
        if ($data['reason'] === '') throw ValidationException::withMessages(['reason' => 'Indicá el motivo del cambio.']);
        return $data;
    }
}
