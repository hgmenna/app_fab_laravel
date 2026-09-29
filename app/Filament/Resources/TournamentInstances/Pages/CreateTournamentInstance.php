<?php

namespace App\Filament\Resources\TournamentInstances\Pages;

use App\Filament\Resources\TournamentInstances\TournamentInstanceResource;
use App\Services\AdminNotifier;
use Filament\Resources\Pages\CreateRecord;

class CreateTournamentInstance extends CreateRecord
{
    protected static string $resource = TournamentInstanceResource::class;

    protected function afterCreate(): void
    {
        AdminNotifier::send($this, $this->record, 'creó', ['code', 'description']);
    }
}
