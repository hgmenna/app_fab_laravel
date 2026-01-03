<?php

namespace App\Filament\Resources\TournamentTypes\Pages;

use App\Filament\Resources\TournamentTypes\TournamentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTournamentTypes extends ListRecords
{
    protected static string $resource = TournamentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
