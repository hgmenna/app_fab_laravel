<?php

namespace App\Filament\Resources\Federations\Pages;

use App\Filament\Resources\Federations\FederationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Services\AdminNotifier;

class EditFederation extends EditRecord
{
    protected static string $resource = FederationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
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
