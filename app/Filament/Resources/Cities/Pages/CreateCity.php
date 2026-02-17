<?php

namespace App\Filament\Resources\Cities\Pages;

use App\Filament\Resources\Cities\CityResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AdminNotifier;

class CreateCity extends CreateRecord
{
    protected static string $resource = CityResource::class;

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
