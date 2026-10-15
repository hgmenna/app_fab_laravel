<?php

namespace App\Filament\Resources\BankTransferSettings\Pages;

use App\Filament\Resources\BankTransferSettings\BankTransferSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankTransferSettings extends ListRecords
{
    protected static string $resource = BankTransferSettingResource::class;
    protected static ?string $title = 'Cuenta para transferencias';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Configurar cuenta')];
    }
}
