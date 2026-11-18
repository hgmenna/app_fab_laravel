<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MobileAssetController extends Controller
{
    public function __invoke(string $file): BinaryFileResponse
    {
        $types = [
            'app.js' => 'text/javascript',
            'ranking.js' => 'text/javascript',
            'registration.js' => 'text/javascript',
            'results.js' => 'text/javascript',
            'install.js' => 'text/javascript',
            'app.css' => 'text/css',
            'icons.svg' => 'image/svg+xml',
            'icon-192.png' => 'image/png',
            'icon-512.png' => 'image/png',
            'install-qr.png' => 'image/png',
            'manifest.webmanifest' => 'application/manifest+json',
        ];
        abort_unless(isset($types[$file]), 404);
        $path = base_path('public/fab-mobile/'.$file);
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => $types[$file],
            'Cache-Control' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
