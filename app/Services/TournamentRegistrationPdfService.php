<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use App\Models\TournamentRegistration;

class TournamentRegistrationPdfService
{
    public static function generate(TournamentRegistration $record): string
    {
        // Normalizar nombres
        $tournament = Str::slug($record->tournament->name);
        $player = Str::slug($record->player->last_name . '-' . $record->player->first_name);

        $fileName = "{$tournament}-{$player}.pdf";

        // Carpeta pública
        $folder = public_path('inscripciones');

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