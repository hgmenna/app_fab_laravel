<?php

namespace App\Filament\Resources\Disciplines\Pages;

use App\Filament\Resources\Disciplines\DisciplineResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AdminNotifier;

class CreateDiscipline extends CreateRecord
{
    protected static string $resource = DisciplineResource::class;

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
