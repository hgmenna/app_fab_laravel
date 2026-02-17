<?php

namespace App\Exceptions;

use App\Services\AdminNotifier;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            Log::error($e);

            // Notificar al administrador la excepcion con el error
            AdminNotifier::sendException($e);
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->is('admin') || $request->is('admin/*')) {
            return response()->view('errors.filament-error', [
                'message' => 'Ocurrió un problema inesperado. El equipo técnico ya fue notificado.'
            ], 500);
        }

        return parent::render($request, $e);
    }

}


