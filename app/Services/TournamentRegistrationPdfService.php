<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use App\Models\TournamentRegistration;
use App\Helpers\FabPath;

class TournamentRegistrationPdfService
{
    public static function generate(TournamentRegistration $record): string
    {
        // Nombre del archivo final
        $tournament = Str::slug($record->tournament->name);
        $player = Str::slug($record->player->last_name . '-' . $record->player->first_name);
        $fileName = "{$tournament}-{$player}.pdf";

        // Carpeta institucional donde guardamos PDFs generados
        $folder = FabPath::inscripciones();

        if (!file_exists($folder)) {
            mkdir($folder, 0775, true);
        }

        $fullPath = FabPath::inscripciones($fileName);

        /*
        |--------------------------------------------------------------------------
        | 1) Resolver ruta del comprobante original
        |--------------------------------------------------------------------------
        | La BD guarda: pagos/archivo.pdf
        | FabPath::pagos() también agrega "pagos/"
        | → Debemos evitar duplicar la carpeta.
        */

        $relative = ltrim($record->payment_file, '/');

        if (str_starts_with($relative, 'pagos/')) {
            // Ruta absoluta directa
            $comprobanteOriginal = FabPath::absolute($relative);
        } else {
            // Ruta relativa → carpeta pagos
            $comprobanteOriginal = FabPath::pagos($relative);
        }

        /*
        |--------------------------------------------------------------------------
        | 2) Determinar si es imagen o PDF
        |--------------------------------------------------------------------------
        */

        $comprobanteImagen = null;
        $comprobantePdf = null;

        if (preg_match('/\.(jpg|jpeg|png)$/i', $comprobanteOriginal)) {
            $comprobanteImagen = $comprobanteOriginal;
        }

        if (preg_match('/\.pdf$/i', $comprobanteOriginal)) {
            $comprobantePdf = $comprobanteOriginal;
        }

        /*
        |--------------------------------------------------------------------------
        | 3) Generar PDF institucional
        |--------------------------------------------------------------------------
        */

        $pdf = Pdf::loadView('pdf.inscription', [
            'record'            => $record,
            'comprobanteImagen' => $comprobanteImagen,
            'comprobantePdf'    => $comprobantePdf,
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
