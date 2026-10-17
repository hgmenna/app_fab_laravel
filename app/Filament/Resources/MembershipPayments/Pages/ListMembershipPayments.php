<?php

namespace App\Filament\Resources\MembershipPayments\Pages;

use App\Filament\Resources\MembershipPayments\MembershipPaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListMembershipPayments extends ListRecords
{
    protected static string $resource = MembershipPaymentResource::class;
    protected static ?string $title = 'Movimientos de membresías';
}
