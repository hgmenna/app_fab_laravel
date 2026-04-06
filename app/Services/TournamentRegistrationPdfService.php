<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use App\Models\TournamentRegistration;

class TournamentRegistrationPdfService
{
    public static function generate(TournamentRegistration $record): string
    {
        $tournament = Str::slug($record->tournament->name);
        $player = Str::slug($record->player->last_name . '-' . $record->player->first_name);

        $fileName = "{$tournament}-{$player}.pdf";

        $publicRoot = '/home/u812683595/domains/sistem.federacionargentinadebillar.org/public_html';
        $folder = "{$publicRoot}/inscripciones";

        if (!file_exists($folder)) {
            mkdir($folder, 0775, true);
        }

        $fullPath = "{$folder}/{$fileName}";

        $pdf = Pdf::loadView('pdf.inscription', [
            'record' => $record,
        ]);

        $pdf->save($fullPath);

        return url("inscripciones/{$fileName}");
    }
}



