<?php

namespace App\Filament\Resources\TournamentTypes\Pages;

use App\Filament\Resources\TournamentTypes\TournamentTypeResource;
use App\Services\AdminNotifier;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditTournamentType extends EditRecord
{
    protected static string $resource = TournamentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AdminNotifier::notifyAction(DeleteAction::make(), $this, 'eliminó', ['name']),
            AdminNotifier::notifyAction(ForceDeleteAction::make(), $this, 'eliminó definitivamente', ['name']),
            AdminNotifier::notifyAction(RestoreAction::make(), $this, 'restauró', ['name']),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        // $this->record es el modelo recién creado
        AdminNotifier::send($this, $this->record, 'actualizó', ['name']);
    }
}
