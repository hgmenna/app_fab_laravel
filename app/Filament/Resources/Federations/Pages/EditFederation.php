<?php

namespace App\Filament\Resources\Federations\Pages;

use App\Filament\Resources\Federations\FederationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFederation extends EditRecord
{
    protected static string $resource = FederationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
