<?php

namespace App\Jobs;

use App\Filament\Resources\Players\Pages\FilteredPlayerPerformance;
use App\Helpers\FabPath;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GeneratePlayerPerformancePdf implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(public string $token, public array $filters) {}

    public function handle(): void
    {
        ini_set('memory_limit', '512M');
        set_time_limit(600);
        try {
            Cache::put('player-performance-pdf:'.$this->token, 'loading', now()->addHour());
            $page = new FilteredPlayerPerformance;
            foreach ($this->filters as $key => $value) $page->$key = $value;
            $report = $page->reportData();
            Cache::put('player-performance-pdf:'.$this->token, 'rendering', now()->addHour());
            $pdf = Pdf::loadView('pdf.filtered-player-performance', [
                'players' => $report['players'], 'totals' => $report['totals'],
                'generatedAt' => now()->format('d/m/Y H:i'),
                'logo' => is_file(FabPath::logo()) ? FabPath::logo() : public_path(config('fab.paths.logo')),
                'footer_image' => is_file(FabPath::footer()) ? FabPath::footer() : public_path(config('fab.paths.footer')),
            ])->setPaper('a4', 'landscape');
            if (! Storage::disk('local')->put('performance-exports/'.$this->token.'.pdf', $pdf->output())) {
                throw new \RuntimeException('No se pudo guardar el PDF de desempeño.');
            }
            Cache::put('player-performance-pdf:'.$this->token, 'ready', now()->addHour());
        } catch (\Throwable $exception) {
            report($exception);
            Cache::put('player-performance-pdf:'.$this->token, 'failed', now()->addHour());
        }
    }
}
