<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Player;
use App\Models\PlayerCategoryHistory;
use App\Services\PlayerCategoryDiscipline;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Diagnostica cruces de disciplinas; solo repara equivalencias inequívocas del historial de ranking con respaldo. */
class AuditPlayerCategoryDisciplines extends Command
{
    protected $signature = 'player-categories:audit-disciplines {--repair-ranking : Corrige únicamente registros de ranking con equivalencia única por código y disciplina}';
    protected $description = 'Detecta categorías de otra disciplina en el historial y permite reparar los cruces automáticos de ranking';

    public function handle(): int
    {
        $invalid = PlayerCategoryHistory::query()->with(['player.discipline', 'previousCategory.discipline', 'category.discipline'])->orderBy('id')->get()
            ->filter(fn($row) => ! $row->player?->discipline_id || ! $row->category
                || (int)$row->category->discipline_id !== (int)$row->player->discipline_id
                || ($row->previous_category_id && (! $row->previousCategory || (int)$row->previousCategory->discipline_id !== (int)$row->player->discipline_id)));
        if ($invalid->isEmpty()) { $this->info('No se detectaron cruces de disciplinas en el historial.'); return self::SUCCESS; }
        $this->table(['ID', 'Jugador', 'Disciplina', 'Anterior', 'Nueva', 'Origen'], $invalid->map(fn($row) => [
            $row->id, $row->player?->full_name ?? '(sin jugador)', $row->player?->discipline?->name ?? '(sin disciplina)',
            ($row->previousCategory?->name ?? '—').' / '.($row->previousCategory?->discipline?->name ?? '—'),
            ($row->category?->name ?? '—').' / '.($row->category?->discipline?->name ?? '—'), $row->source,
        ])->all());
        $plans = [];
        foreach ($invalid as $row) {
            if ($row->source !== 'ranking' || ! $row->player?->discipline_id || ! $row->category) continue;
            $target = $this->equivalent($row->category, (int)$row->player->discipline_id);
            $previous = $row->previous_category_id ? $this->equivalent($row->previousCategory, (int)$row->player->discipline_id) : null;
            if (! $target || ($row->previous_category_id && ! $previous)) continue;
            $plans[] = ['id' => $row->id, 'before' => $row->getRawOriginal(), 'category_id' => $target,
                'previous_category_id' => $previous, 'delete' => $previous !== null && $target === $previous];
        }
        $this->info('Registros incorrectos: '.$invalid->count().'. Reparables automáticamente de ranking: '.count($plans).'.');
        if (! $this->option('repair-ranking')) {
            $this->line('Diagnóstico solamente: no se modificó ningún dato. Para aplicar las correcciones con respaldo usá --repair-ranking.');
            return self::SUCCESS;
        }
        if (! $plans) { $this->warn('No hay equivalencias inequívocas que puedan corregirse automáticamente.'); return self::SUCCESS; }
        $backup = 'repairs/category-disciplines-'.now()->format('Ymd-His').'-'.Str::random(8).'.json';
        if (! Storage::disk('local')->put($backup, json_encode(['created_at' => now()->toIso8601String(), 'records' => $plans], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR))) {
            $this->error('No se pudo crear el respaldo. No se modificó ningún dato.'); return self::FAILURE;
        }
        DB::transaction(function () use ($plans) {
            foreach ($plans as $plan) {
                Player::query()->whereKey($plan['before']['player_id'])->lockForUpdate()->firstOrFail();
                $row = PlayerCategoryHistory::query()->lockForUpdate()->findOrFail($plan['id']);
                if ($row->getRawOriginal() != $plan['before']) throw new \RuntimeException('El historial cambió durante la revisión. Volvé a ejecutar el diagnóstico.');
                PlayerCategoryDiscipline::validateIds($row->player_id, $plan['category_id'], $plan['previous_category_id']);
                if ($plan['delete']) {
                    // Master → Máxima, ambas código M, se convierte en Master → Master: no existió un cambio real.
                    DB::table('player_category_histories')->where('id', $row->id)->delete();
                } else {
                    $row->update(['category_id' => $plan['category_id'], 'previous_category_id' => $plan['previous_category_id']]);
                }
            }
        });
        $this->info('Corregidos: '.count($plans).'. Respaldo: '.Storage::disk('local')->path($backup));
        if ($invalid->count() > count($plans)) $this->warn('Quedan '.($invalid->count() - count($plans)).' registros que requieren revisión individual; no fueron modificados.');
        return self::SUCCESS;
    }

    /** Nunca adivina por nombre ni toma categorías de otra disciplina. */
    private function equivalent(?Category $category, int $disciplineId): ?int
    {
        if (! $category || ! filled($category->code)) return null;
        $matches = Category::query()->where('discipline_id', $disciplineId)->where('code', $category->code)->pluck('id');
        return $matches->count() === 1 ? (int)$matches->first() : null;
    }
}
