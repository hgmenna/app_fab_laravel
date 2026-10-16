<?php

namespace App\Http\Controllers;

use App\Models\BankTransferOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class BankTransferOrderPdfController extends Controller
{
    public function __invoke(BankTransferOrder $order): Response
    {
        $order->load(['items.payable', 'club', 'discipline', 'user']);

        return Pdf::loadView('pdf.bank-transfer-order', [
            'order' => $order,
        ])->download("orden-{$order->code}.pdf");
    }
}
