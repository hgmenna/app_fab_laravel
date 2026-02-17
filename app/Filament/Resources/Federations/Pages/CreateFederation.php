<?php

namespace App\Filament\Resources\Federations\Pages;

use App\Filament\Resources\Federations\FederationResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AdminNotifier;

class CreateFederation extends CreateRecord
{
    protected static string $resource = FederationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // $this->record es el modelo recién creado
        AdminNotifier::send($this, $this->record, 'creó', ['name']);
    }
}
