<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Services\AdminNotifier;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPlayer extends EditRecord
{
    protected static string $resource = PlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AdminNotifier::notifyAction(DeleteAction::make(), $this, 'eliminó', ['last_name', 'first_name']),
            AdminNotifier::notifyAction(ForceDeleteAction::make(), $this, 'eliminó definitivamente', ['last_name', 'first_name']),
            AdminNotifier::notifyAction(RestoreAction::make(), $this, 'restauró', ['last_name', 'first_name']),
        ];
    }

    protected function afterSave(): void
    {
        // $this->record es el modelo recién creado
        AdminNotifier::send($this, $this->record, 'actualizó', ['last_name', 'first_name']);
    }
}
