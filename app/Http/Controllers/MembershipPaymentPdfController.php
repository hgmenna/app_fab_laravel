<?php

namespace App\Http\Controllers;

use App\Helpers\FabPath;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class MembershipPaymentPdfController extends Controller
{
    public function __invoke(Payment $payment): Response
    {
        $payment->load(['membership.player.club', 'membership.membership.discipline', 'payer', 'recorder']);

        return Pdf::loadView('pdf.membership-payment', [
            'payment' => $payment,
            'account' => $payment->membership,
            'logo' => is_file(FabPath::logo()) ? FabPath::logo() : null,
            'footerImage' => is_file(FabPath::footer()) ? FabPath::footer() : null,
        ])->setPaper('a4', 'portrait')->stream('comprobante-movimiento-'.$payment->id.'.pdf');
    }
}
