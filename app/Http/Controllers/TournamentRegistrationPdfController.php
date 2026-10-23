<?php

namespace App\Http\Controllers;

use App\Models\TournamentRegistration;
use App\Services\TournamentRegistrationPdfService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Genera el comprobante PDF de una inscripción a torneo.
 */
class TournamentRegistrationPdfController extends Controller
{
    /**
     * Atiende la solicitud después de resolver autorización y datos publicados.
     */
    public function __invoke(TournamentRegistration $registration): Response
    {
        return TournamentRegistrationPdfService::pdf($registration)
            ->download(TournamentRegistrationPdfService::fileName($registration));
    }
}
