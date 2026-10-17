<?php

namespace App\Filament\Resources\MembershipPayments\Pages;

use App\Filament\Resources\MembershipPayments\MembershipPaymentResource;
use App\Services\AdminNotifier;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\URL;

class EditMembershipPayment extends EditRecord
{
    protected static string $resource = MembershipPaymentResource::class;
    protected static ?string $title = 'Detalle del movimiento';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')->label('Ver / imprimir')->icon('heroicon-o-printer')->openUrlInNewTab()
                ->url(fn (): string => URL::temporarySignedRoute('membership-payments.pdf', now()->addDays(7), ['payment'=>$this->record])),
        ];
    }

    protected function afterSave(): void
    {
        AdminNotifier::send($this, $this->record, 'actualizó la observación del movimiento', 'id', 'Tesorería');
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
