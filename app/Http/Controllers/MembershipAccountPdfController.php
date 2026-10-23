<?php

namespace App\Http\Controllers;

use App\Helpers\FabPath;
use App\Models\PlayerMembership;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Genera el estado individual de una cuenta de afiliación.
 */
class MembershipAccountPdfController extends Controller
{
    /**
     * Atiende la solicitud después de resolver autorización y datos publicados.
     */
    public function __invoke(PlayerMembership $account): Response
    {
        $account->load(['player.club', 'membership.discipline', 'club', 'payments.payer', 'payments.recorder']);

        return Pdf::loadView('pdf.membership-account', [
            'account' => $account,
            'logo' => is_file(FabPath::logo()) ? FabPath::logo() : null,
            'footerImage' => is_file(FabPath::footer()) ? FabPath::footer() : null,
        ])->setPaper('a4', 'portrait')->stream('cuenta-corriente-membresia-'.$account->id.'.pdf');
    }
}
