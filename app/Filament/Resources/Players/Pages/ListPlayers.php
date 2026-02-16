<?php

namespace App\Filament\Resources\Players\Pages;


use App\Filament\Resources\Players\PlayerResource;
use App\Filament\Resources\Players\Widgets\PlayerChart;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\PlayersImport;
use Illuminate\Support\Facades\Auth;


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
            CreateAction::make()->label('Nuevo Jugador'),
            Action::make('importPlayers')
                ->label('Importar jugadores')
                ->visible(fn () => Auth::user()->can('EditField'))
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
