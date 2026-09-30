<?php

namespace App\Filament\Resources\DisciplineModalities\Pages;

use App\Filament\Resources\DisciplineModalities\DisciplineModalityResource;
use App\Services\AdminNotifier;
use Filament\Resources\Pages\CreateRecord;

class CreateDisciplineModality extends CreateRecord
{
    protected static string $resource = DisciplineModalityResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        AdminNotifier::send($this, $this->record, 'creó', ['name']);
    }
}
