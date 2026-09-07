<?php

namespace App\Console\Commands;

use App\Services\PlayerCategoryChangeService;
use Illuminate\Console\Command;

class ApplyDuePlayerCategoryChanges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'player-categories:apply-due-changes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aplica los cambios manuales de categoría cuya fecha efectiva ya fue alcanzada';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $applied = PlayerCategoryChangeService::applyDueChanges();

        if ($applied === 0) {
            $this->info('No hay cambios manuales de categoría pendientes para aplicar.');

            return self::SUCCESS;
        }

        $this->info(
            "Cambios manuales de categoría aplicados correctamente: {$applied}"
        );

        return self::SUCCESS;
    }
}
