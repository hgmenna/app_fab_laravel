<?php

namespace App\Services;

use App\Helpers\FabPath;
use App\Models\TournamentRegistration;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class TournamentRegistrationPdfService
{
    public static function fileName(TournamentRegistration $record): string
    {
        $record->loadMissing(['participants.player']);
        $tournament = Str::slug($record->tournament->name);
        $player = $record->participants->map(fn ($participant) => Str::slug($participant->player->full_name))->implode('-y-');

        return "{$tournament}-{$player}.pdf";
    }

    public static function pdf(TournamentRegistration $record)
    {
        $record->loadMissing([
            'participants.player.club',
            'participants.player.category',
            'tournament.contacts',
            'tournamentModality.modality',
            'slot',
        ]);

        $relative = ltrim((string) $record->payment_file, '/');
        $comprobanteOriginal = '';

        if ($relative !== '') {
            $comprobanteOriginal = str_starts_with($relative, 'pagos/')
                ? FabPath::absolute($relative)
                : FabPath::pagos($relative);
        }

        $comprobanteImagen = preg_match('/\.(jpg|jpeg|png)$/i', $comprobanteOriginal)
            ? $comprobanteOriginal
            : null;
        $comprobantePdf = preg_match('/\.pdf$/i', $comprobanteOriginal)
            ? $comprobanteOriginal
            : null;

        return Pdf::loadView('pdf.inscription', [
            'record' => $record,
            'comprobanteImagen' => $comprobanteImagen,
            'comprobantePdf' => $comprobantePdf,
        ])
            ->setPaper('A4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);
    }

    public static function generate(TournamentRegistration $record): string
    {
        $fileName = self::fileName($record);

        // Carpeta institucional donde guardamos PDFs generados
        $folder = FabPath::inscripciones();

        if (! file_exists($folder)) {
            mkdir($folder, 0775, true);
        }

        $fullPath = FabPath::inscripciones($fileName);

        self::pdf($record)->save($fullPath);

        return url("inscripciones/{$fileName}");
    }
}
