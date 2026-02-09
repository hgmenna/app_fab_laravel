<?php

namespace App\Filament\Resources\Cities\Pages;

use App\Filament\Resources\Cities\CityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCities extends ListRecords
{
    protected static string $resource = CityResource::class;
    protected static ?string $title = 'Ciudades';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva Localidad'),
        ];
    }
}
