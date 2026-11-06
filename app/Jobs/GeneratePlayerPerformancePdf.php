<?php

namespace App\Jobs;

use App\Filament\Resources\Players\Pages\FilteredPlayerPerformance;
use App\Helpers\FabPath;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GeneratePlayerPerformancePdf
{

    public function __construct(public string $token, public array $filters) {}

    public function handle(): void
    {
        ini_set('memory_limit', '512M');
        set_time_limit(600);
        try {
            $page = new FilteredPlayerPerformance;
            foreach ($this->filters as $key => $value) $page->$key = $value;
            $report = $page->reportData();
            $pdf = Pdf::loadView('pdf.filtered-player-performance', [
                'players' => $report['players'], 'totals' => $report['totals'],
                'generatedAt' => now()->format(config('cliente.formato_fecha').' H:i'),
                'logo' => is_file(FabPath::logo()) ? FabPath::logo() : public_path(config('fab.paths.logo')),
                'footer_image' => is_file(FabPath::footer()) ? FabPath::footer() : public_path(config('fab.paths.footer')),
            ])->setPaper('a4', 'landscape');
            if (! Storage::disk('local')->put('performance-exports/'.$this->token.'.pdf', $pdf->output())) {
                throw new \RuntimeException('No se pudo guardar el PDF de desempeño.');
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
