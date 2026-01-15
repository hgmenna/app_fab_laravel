<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PlayersImport;

class ListPlayers extends ListRecords
{
    protected static string $resource = PlayerResource::class;
    protected static ?string $title = 'Listado de Jugadores';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo Jugador'),
            Action::make('importPlayers')
                ->label('Importar jugadores')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->schema([
                    FileUpload::make('file')
                        ->label('Archivo Excel')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    Excel::import(new PlayersImport, $data['file']);
                })
                ->modalHeading('Importar jugadores desde Excel')
                ->modalSubmitActionLabel('Importar'),

        ];
    }


}
