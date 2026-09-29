<?php

namespace App\Filament\Resources\TournamentRegulationSettings\Pages;

use App\Filament\Resources\TournamentRegulationSettings\TournamentRegulationSettingResource;
use App\Services\AdminNotifier;
use Filament\Resources\Pages\CreateRecord;

class CreateTournamentRegulationSetting extends CreateRecord
{
    protected static string $resource = TournamentRegulationSettingResource::class;

    protected function afterCreate(): void
    {
        AdminNotifier::send($this, $this->record, 'creó', ['discipline.name'], 'Configuración reglamentaria');
    }
}
