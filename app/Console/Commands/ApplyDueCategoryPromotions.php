<?php

namespace App\Console\Commands;

use App\Services\PlayerCategoryPromotionService;
use Illuminate\Console\Command;
use Throwable;

class ApplyDueCategoryPromotions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ranking:apply-category-promotions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aplica los ascensos de categoría cuya fecha efectiva ya llegó';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $applied = PlayerCategoryPromotionService::applyDuePromotions();

            if ($applied === 0) {
                $this->info(
                    'No hay ascensos de categoría pendientes para aplicar.'
                );

                return self::SUCCESS;
            }

            $this->info(
                "Ascensos de categoría aplicados correctamente: {$applied}."
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}