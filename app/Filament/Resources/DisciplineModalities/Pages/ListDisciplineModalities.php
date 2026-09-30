<?php

namespace App\Filament\Resources\DisciplineModalities\Pages;

use App\Filament\Resources\DisciplineModalities\DisciplineModalityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDisciplineModalities extends ListRecords
{
    protected static string $resource = DisciplineModalityResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva modalidad')];
    }
}
