<?php

namespace App\Http\Controllers;

use App\Models\BankTransferOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * Descarga el comprobante de una orden de transferencia mediante una URL autorizada.
 */
class BankTransferOrderPdfController extends Controller
{
    /**
     * Atiende la solicitud después de resolver autorización y datos publicados.
     */
    public function __invoke(BankTransferOrder $order): Response
    {
        $order->load(['items.payable', 'club', 'discipline', 'user']);

        return Pdf::loadView('pdf.bank-transfer-order', [
            'order' => $order,
        ])->download("orden-{$order->code}.pdf");
    }
}
