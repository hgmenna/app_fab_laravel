<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\GeneratePlayerPerformancePdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PlayerPerformancePdfDownloadController extends Controller
{
    public function __invoke(Request $request, string $token): BinaryFileResponse
    {
        $selection = $request->session()->get('player-performance-pdf.'.$token);
        abort_unless(is_array($selection) && ($selection['user_id'] ?? null) === Auth::id()
            && ($selection['expires_at'] ?? 0) > now()->timestamp, 404);
        $disk = Storage::disk('local');
        $path = 'performance-exports/'.$token.'.pdf';
        if (! $disk->exists($path) && is_array($selection['filters'] ?? null)) {
            // El navegador solicita el archivo por HTTP, fuera de Livewire.
            // No requiere procesos CLI, workers ni callbacks posteriores a la respuesta.
            (new GeneratePlayerPerformancePdf($token, $selection['filters']))->handle();
        }
        abort_unless($disk->exists($path), 404);

        return response()->download($disk->path($path), 'desempeno-jugadores-'.now()->format('Y-m-d').'.pdf', [
            'Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }
}
