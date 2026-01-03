<?php

namespace App\Filament\Resources\TournamentTypes\Pages;

use App\Filament\Resources\TournamentTypes\TournamentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTournamentType extends EditRecord
{
    protected static string $resource = TournamentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
