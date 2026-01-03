<?php

namespace App\Filament\Resources\Federations\Pages;

use App\Filament\Resources\Federations\FederationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFederations extends ListRecords
{
    protected static string $resource = FederationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
