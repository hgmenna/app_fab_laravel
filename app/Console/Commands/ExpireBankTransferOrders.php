<?php

namespace App\Console\Commands;

use App\Models\BankTransferOrder;
use Illuminate\Console\Command;

class ExpireBankTransferOrders extends Command
{
    protected $signature = 'bank-transfers:expire';

    protected $description = 'Marca como vencidas las órdenes de transferencia que no presentaron comprobante';

    public function handle(): int
    {
        $count = BankTransferOrder::query()
            ->where('status', 'pending')
            ->whereDate('expires_at', '<', today())
            ->update(['status' => 'expired']);

        $this->info("Órdenes vencidas: {$count}");

        return self::SUCCESS;
    }
}
