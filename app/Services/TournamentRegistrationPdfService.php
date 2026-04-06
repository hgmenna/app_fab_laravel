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
        $publicRoot = '/home/u812683595/domains/sistem.federacionargentinadebillar.org/public_html';

        $folder = "{$publicRoot}/inscripciones";

        if (!file_exists($folder)) {
            mkdir($folder, 0775, true);
        }

        /*
        |--------------------------------------------------------------------------
        | 🔥 CONVERSIÓN SEGURA DEL COMPROBANTE (sin tocar el archivo original)
        |--------------------------------------------------------------------------
        */
        $convertedPath = null;

        if ($record->payment_file) {

            $relative = ltrim($record->payment_file, '/');
            $original = "{$publicRoot}/{$relative}";

            // Si existe y NO es PDF → convertir
            if (file_exists($original) && !str_ends_with(strtolower($original), '.pdf')) {

                // Crear carpeta temporal
                $tempFolder = "{$publicRoot}/inscripciones/temp";
                if (!file_exists($tempFolder)) {
                    mkdir($tempFolder, 0775, true);
                }

                // Crear copia temporal
                $convertedPath = "{$tempFolder}/" . basename($original);
                copy($original, $convertedPath);

                // Convertir la copia a JPEG baseline
                Image::read($convertedPath)
                    ->toJpeg(85)
                    ->save($convertedPath);
            }
        }

        $fullPath = "{$folder}/{$fileName}";

        /*
        |--------------------------------------------------------------------------
        | Generar PDF desde la vista Blade
        |--------------------------------------------------------------------------
        */
        $pdf = Pdf::loadView('pdf.inscription', [
            'record' => $record,
            'converted_payment_file' => $convertedPath, // ← clave
        ]);

        // Guardar archivo
        $pdf->save($fullPath);

        // URL pública accesible
        return url("inscripciones/{$fileName}");
    }
}

