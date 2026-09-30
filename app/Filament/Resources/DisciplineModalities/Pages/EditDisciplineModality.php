<?php

namespace App\Filament\Resources\DisciplineModalities\Pages;

use App\Filament\Resources\DisciplineModalities\DisciplineModalityResource;
use App\Services\AdminNotifier;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDisciplineModality extends EditRecord
{
    protected static string $resource = DisciplineModalityResource::class;

    protected function getHeaderActions(): array
    {
        return [AdminNotifier::notifyAction(DeleteAction::make(), $this, 'eliminó', ['name'])];
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
