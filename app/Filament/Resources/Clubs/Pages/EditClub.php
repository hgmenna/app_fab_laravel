<?php

namespace App\Filament\Resources\Clubs\Pages;

use App\Filament\Resources\Clubs\ClubResource;
use App\Services\AdminNotifier;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditClub extends EditRecord
{
    protected static string $resource = ClubResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AdminNotifier::notifyAction(DeleteAction::make()->label('Eliminar'), $this, 'eliminó', ['name']),
            AdminNotifier::notifyAction(ForceDeleteAction::make(), $this, 'eliminó definitivamente', ['name']),
            AdminNotifier::notifyAction(RestoreAction::make()->label('Restaurar'), $this, 'restauró', ['name']),
        ];
    }

    public function getTitle(): string
    {
        return 'Actualizar: '.$this->record->name;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        AdminNotifier::send($this, $this->record, 'actualizó', ['name']);
    }
}
