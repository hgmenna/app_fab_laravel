<?php

namespace App\Filament\Resources\TournamentInstances\Pages;

use App\Filament\Resources\TournamentInstances\TournamentInstanceResource;
use App\Services\AdminNotifier;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTournamentInstance extends EditRecord
{
    protected static string $resource = TournamentInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AdminNotifier::notifyAction(DeleteAction::make(), $this, 'eliminó', ['code', 'description']),
        ];
    }

    protected function afterSave(): void
    {
        AdminNotifier::send($this, $this->record, 'actualizó', ['code', 'description']);
    }
}
