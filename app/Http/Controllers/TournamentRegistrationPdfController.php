<?php

namespace App\Http\Controllers;

use App\Models\TournamentRegistration;
use App\Services\TournamentRegistrationPdfService;
use Symfony\Component\HttpFoundation\Response;

class TournamentRegistrationPdfController extends Controller
{
    public function __invoke(TournamentRegistration $registration): Response
    {
        return TournamentRegistrationPdfService::pdf($registration)
            ->download(TournamentRegistrationPdfService::fileName($registration));
    }
}
