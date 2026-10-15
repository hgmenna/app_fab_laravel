<?php

namespace App\Filament\Resources\BankTransferOrders\Pages;

use App\Filament\Resources\BankTransferOrders\BankTransferOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListBankTransferOrders extends ListRecords
{
    protected static string $resource = BankTransferOrderResource::class;
    protected static ?string $title = 'Pagos por transferencia';
}
