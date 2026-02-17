<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AdminNotifier;



class CreateTournament extends CreateRecord
{
    protected static string $resource = TournamentResource::class;

    public static function getFormWidth(): string|int|null
    {
        return 'full';
    }

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
