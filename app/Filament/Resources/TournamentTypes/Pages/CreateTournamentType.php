<?php

namespace App\Filament\Resources\TournamentTypes\Pages;

use App\Filament\Resources\TournamentTypes\TournamentTypeResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AdminNotifier;

class CreateTournamentType extends CreateRecord
{
    protected static string $resource = TournamentTypeResource::class;

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
