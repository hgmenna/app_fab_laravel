<?php

namespace App\Filament\Resources\MembershipAccounts\Pages;

use App\Filament\Resources\MembershipAccounts\MembershipAccountResource;
use App\Filament\Resources\Players\PlayerResource;
use Filament\Resources\Pages\ListRecords;

class ListMembershipAccounts extends ListRecords
{
    protected static string $resource = MembershipAccountResource::class;
    protected static ?string $title = 'Cuenta corriente de membresías';

    protected function getHeaderActions(): array
    {
        return [PlayerResource::renewClubMembershipsAction()];
    }
}
