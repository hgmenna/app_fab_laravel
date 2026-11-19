<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Player;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Los códigos de categoría solo identifican una categoría dentro de su disciplina. */
class PlayerCategoryDiscipline
{
    public static function categories(int $disciplineId): Collection
    {
        $categories = Category::query()->where('discipline_id', $disciplineId)->get();
        if ($categories->filter(fn($category) => filled($category->code))->groupBy('code')->contains(fn($items) => $items->count() > 1)) {
            throw ValidationException::withMessages(['category_id' => 'Hay códigos de categoría repetidos dentro de la misma disciplina. Revisá la configuración.']);
        }
        return $categories->keyBy('code');
    }

    /** Se usa al guardar y antes de aplicar registros antiguos, incluidos cambios programados. */
    public static function validateIds(int $playerId, int $categoryId, ?int $previousId = null): void
    {
        $discipline = Player::query()->whereKey($playerId)->value('discipline_id');
        $ids = collect([$categoryId, $previousId])->filter()->unique();
        $valid = $discipline ? Category::query()->where('discipline_id', $discipline)->whereIn('id', $ids)->count() : 0;
        if (! $discipline || ! $categoryId || $valid !== $ids->count()) {
            throw ValidationException::withMessages(['category_id' => 'La categoría anterior y la nueva deben pertenecer a la disciplina del jugador.']);
        }
    }
}
