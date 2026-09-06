<?php

namespace App\Console\Commands;

use App\Services\PlayerCategoryPromotionService;
use Illuminate\Console\Command;
use Throwable;

class DetermineCategoryPromotions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ranking:determine-category-promotions {season}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Determina los ascensos de categoría a partir del RankingHistory de una temporada cerrada';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $season = (int) $this->argument('season');

            if ($season <= 0) {
                $this->error('La temporada indicada no es válida.');

                return self::FAILURE;
            }

            $count = PlayerCategoryPromotionService::determineSeasonPromotions(
                $season
            );

            if ($count === 0) {
                $this->info(
                    "No se determinaron ascensos de categoría para la temporada {$season}."
                );

                return self::SUCCESS;
            }

            $this->info(
                "Ascensos de categoría determinados para la temporada {$season}: {$count}."
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}