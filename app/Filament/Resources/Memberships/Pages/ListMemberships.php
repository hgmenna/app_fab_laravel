<?php

namespace App\Filament\Resources\Memberships\Pages;

use App\Filament\Resources\Memberships\MembershipResource;
use App\Filament\Resources\Players\PlayerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListMemberships extends ListRecords
{
    protected static string $resource = MembershipResource::class;

    protected static ?string $title = 'Membresías anuales';

    protected function getHeaderActions(): array
    {
        return [
            PlayerResource::renewClubMembershipsAction(),
            CreateAction::make()
                ->label('Configurar nuevo año')
                ->visible(fn (): bool => Auth::user()?->hasRole('super-admin') ?? false),
        ];
    }
}
