<?php

namespace App\Filament\Resources\TournamentInstances\Pages;

use App\Filament\Resources\TournamentInstances\TournamentInstanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTournamentInstance extends EditRecord
{
    protected static string $resource = TournamentInstanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
