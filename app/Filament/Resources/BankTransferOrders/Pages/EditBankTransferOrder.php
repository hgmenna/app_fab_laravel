<?php

namespace App\Filament\Resources\BankTransferOrders\Pages;

use App\Filament\Resources\BankTransferOrders\BankTransferOrderResource;
use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Services\BankTransferService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditBankTransferOrder extends EditRecord
{
    protected static string $resource = BankTransferOrderResource::class;
    protected static ?string $title = 'Orden de transferencia';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addTournamentRegistration')
                ->label('Agregar otra inscripción')
                ->icon('heroicon-o-user-plus')
                ->iconButton()
                ->tooltip('Agregar otra inscripción a esta orden')
                ->color('info')
                ->visible(fn (): bool => $this->record->purpose === 'tournament_registration'
                    && $this->record->status === 'pending'
                    && blank($this->record->receipt_path)
                    && $this->registrationTournament() !== null)
                ->url(fn (): string => TournamentResource::getUrl('registrations', [
                    'record' => $this->registrationTournament(),
                ])),
            BankTransferOrderResource::shareByWhatsAppAction(),
            Action::make('submit')->label('Enviar a revisión')->icon('heroicon-o-paper-airplane')->color('primary')
                ->visible(fn (): bool => in_array($this->record->status, ['pending', 'observed'], true))
                ->action(function (): void {
                    $this->save();
                    app(BankTransferService::class)->submit($this->record->refresh());
                    $this->redirect(BankTransferOrderResource::getUrl('index'));
                }),
        ];
    }

    protected function getFormActions(): array
    {
        if (! in_array($this->record->status, ['pending', 'observed'], true)) {
            return [];
        }

        return parent::getFormActions();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        abort_unless($this->record->user_id === auth()->id() || auth()->user()?->hasRole('super-admin'), 403);
        abort_unless(in_array($this->record->status, ['pending', 'observed'], true), 403);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return BankTransferOrderResource::getUrl('index');
    }

    private function registrationTournament(): ?Tournament
    {
        $item = $this->record->items()
            ->where('payable_type', (new TournamentRegistration)->getMorphClass())
            ->with('payable.tournament')
            ->first();

        return $item?->payable?->tournament;
    }
}
