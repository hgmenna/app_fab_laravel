<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use App\Models\TournamentRegistration;
use Intervention\Image\Laravel\Facades\Image;


class TournamentRegistrationPdfService
{
    public static function generate(TournamentRegistration $record): string
    {
        // Normalizar nombres
        $tournament = Str::slug($record->tournament->name);
        $player = Str::slug($record->player->last_name . '-' . $record->player->first_name);

        $fileName = "{$tournament}-{$player}.pdf";

        // Ruta pública REAL del hosting compartido
        $publicRoot = '/home/u812683595/domains/sistem.federacionargentinadebillar.org/public_html';  // ← clave

        $folder = "{$publicRoot}/inscripciones";

        /*
        |--------------------------------------------------------------------------
        | 🔥 CONVERSIÓN AL VUELO DEL COMPROBANTE
        |--------------------------------------------------------------------------
        */
        if ($record->payment_file) {
            $relative = ltrim($record->payment_file, '/');
            $absolute = "{$publicRoot}/{$relative}";

            // Si es imagen y existe → convertir a JPEG baseline
            if (file_exists($absolute) && !str_ends_with(strtolower($absolute), '.pdf')) {
                Image::read($absolute)
                    ->toJpeg(85)
                    ->save($absolute);
                        // Sobrescribe el archivo
            }
        }

        if (!file_exists($folder)) {
            mkdir($folder, 0775, true);
        }

        $fullPath = "{$folder}/{$fileName}";

        // Generar PDF desde la vista Blade
        $pdf = Pdf::loadView('pdf.inscription', [
            'record' => $record,
        ]);

        // Guardar archivo
        $pdf->save($fullPath);

        // URL pública accesible
        return url("inscripciones/{$fileName}");
    }
}