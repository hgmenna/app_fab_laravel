<?php

namespace App\Services;

use App\Helpers\FabPath;
use App\Helpers\PdfHelper;
use App\Models\TournamentRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class TournamentRegistrationPdfService
{
    public static function generate(TournamentRegistration $record): string
    {
        $tournament = Str::slug($record->tournament->name);
        $player = Str::slug($record->player->last_name . '-' . $record->player->first_name);

        $fileName = "{$tournament}-{$player}.pdf";

        //$publicRoot = '/home/u812683595/domains/sistem.federacionargentinadebillar.org/public_html';
        // Carpeta para guardar los pdf generados
        $folder = FabPath::inscripciones();

        if (!file_exists($folder)) {
            mkdir($folder, 0775, true);
        }

        $fullPath = FabPath::inscripciones($fileName);

        // Ruta del comprobante original (PDF o imagen)
        $relative = ltrim($record->payment_file, '/');
        $comprobantePdf = FabPath::pagos($relative);

        // Ruta PNG convertida
        $comprobantePng = str_replace('.pdf', '.png', $comprobantePdf);

        // Convertir PDF → PNG si corresponde
        if (str_ends_with(strtolower($comprobantePdf), '.pdf')) {
            PdfHelper::pdfToPng($comprobantePdf, $comprobantePng);
        }

        $pdf = Pdf::loadView('pdf.inscription', [
            'record' => $record,
        ])
            ->setPaper('A4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        $pdf->save($fullPath);

        return url("inscripciones/{$fileName}");

    }
}



