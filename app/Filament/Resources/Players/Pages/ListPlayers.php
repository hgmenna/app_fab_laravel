<?php

namespace App\Filament\Resources\Players\Pages;


use App\Filament\Resources\Players\PlayerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPlayers extends ListRecords
{
    protected static string $resource = PlayerResource::class;
    protected static ?string $title = 'Listado de Jugadores';

    protected function getHeaderWidgets(): array
    {
        return [
            //PlayerChart::class, // Registra el widget aquí
           // PlayerStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo'),
        ];
    }


}
