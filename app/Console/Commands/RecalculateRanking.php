<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Ranking5QuillasService;

class RecalculateRanking extends Command
{
    protected $signature = 'ranking:recalculate';
    protected $description = 'Recalcula el ranking nacional de 5 Quillas';

    public function handle(Ranking5QuillasService $service)
    {
        $service->recalculate();
        $this->info('Ranking recalculado correctamente.');
    }
}
