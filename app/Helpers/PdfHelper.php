<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class PdfHelper
{
    public static function pdfToPng(string $pdfPath, string $pngPath): bool
    {
        if (!file_exists($pdfPath)) {
            return false;
        }

        // Si ya existe el PNG, no lo regeneramos
        if (file_exists($pngPath)) {
            return true;
        }

        // Llamada a la API externa
        $response = Http::attach(
            'file', file_get_contents($pdfPath), basename($pdfPath)
        )->post('https://api.pdf2png.com/v1/convert', [
            'output' => 'png',
        ]);

        // Laravel 12: usar json() u object()
        $data = $response->json();

        // Validar respuesta
        if (!is_array($data) || !isset($data['png'])) {
            return false;
        }

        // La API devuelve base64 → lo decodificamos
        $pngBinary = base64_decode($data['png']);

        if (!$pngBinary) {
            return false;
        }

        file_put_contents($pngPath, $pngBinary);

        return true;
    }
}

