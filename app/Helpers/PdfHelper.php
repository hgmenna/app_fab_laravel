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

        if (file_exists($pngPath)) {
            return true;
        }

        // Llamada a la API externa
        $response = Http::attach(
            'file', file_get_contents($pdfPath), basename($pdfPath)
        )->post('https://api.pdf2png.com/v1/convert', [
            'output' => 'png',
        ]);

        // Convertimos la respuesta a string (binario)
        $binary = (string) $response;

        // Si está vacío, falló
        if (empty($binary)) {
            return false;
        }

        // Guardamos el PNG
        file_put_contents($pngPath, $binary);

        return true;
    }
}