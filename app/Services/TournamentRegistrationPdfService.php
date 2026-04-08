<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use App\Models\TournamentRegistration;
use App\Helpers\FabPath;
use App\Helpers\PdfHelper;

class TournamentRegistrationPdfService
{
    public static function generate(TournamentRegistration $record): string
    {
        $tournament = Str::slug($record->tournament->name);
        $player = Str::slug($record->player->last_name . '-' . $record->player->first_name);

        $fileName = "{$tournament}-{$player}.pdf";

        // Carpeta donde guardamos PDFs generados
        $folder = FabPath::inscripciones();

        if (!file_exists($folder)) {
            mkdir($folder, 0775, true);
        }

        $fullPath = FabPath::inscripciones($fileName);

        // Ruta del comprobante original (PDF o imagen)
        $relative = ltrim($record->payment_file, '/');

        // Si ya viene con "pagos/" no lo duplicamos
        if (str_starts_with($relative, 'pagos/')) {
            $comprobanteOriginal = FabPath::absolute($relative);
        } else {
            $comprobanteOriginal = FabPath::pagos($relative);
        }

        // Si es PDF → convertir a PNG
        $comprobanteImagen = null;

        if (str_ends_with(strtolower($comprobanteOriginal), '.pdf')) {

            $pngPath = preg_replace('/\.pdf$/i', '.png', $comprobanteOriginal);

            if (PdfHelper::pdfToPng($comprobanteOriginal, $pngPath)) {
                $comprobanteImagen = $pngPath;
            }

        } else {
            // Si es imagen, usarla directamente
            $comprobanteImagen = $comprobanteOriginal;
        }

        // Generar PDF institucional
        $pdf = Pdf::loadView('pdf.inscription', [
            'record' => $record,
            'comprobanteImagen' => $comprobanteImagen,
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