<?php

namespace App\Console\Commands;

use App\Services\RankingService;
use Illuminate\Console\Command;
use Throwable;

class CloseSeasonRanking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ranking:close-season {season}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Guarda el Ranking Histórico de una temporada';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $season = (int) $this->argument('season');

        try {
            RankingService::saveSeasonRanking($season);

            $this->info(
                "Ranking histórico {$season} generado correctamente."
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}