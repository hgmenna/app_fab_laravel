<?php

namespace App\Http\Controllers;

use App\Models\BankTransferOrder;
use App\Models\BankTransferSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class BankTransferOrderPdfController extends Controller
{
    public function __invoke(BankTransferOrder $order): Response
    {
        $order->load(['items.payable', 'club', 'discipline', 'user']);

        return Pdf::loadView('pdf.bank-transfer-order', [
            'order' => $order,
            'setting' => BankTransferSetting::active(),
        ])->download("orden-{$order->code}.pdf");
    }
}
