<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTournaments extends ListRecords
{
    protected static string $resource = TournamentResource::class;

    protected static ?string $title = 'Torneos';

    public function mount(): void
    {
        parent::mount();

        $this->tableSort = 'start_date:asc';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo Torneo'),
        ];
    }
}
