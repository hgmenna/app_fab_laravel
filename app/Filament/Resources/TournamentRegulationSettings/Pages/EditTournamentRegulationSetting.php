<?php

namespace App\Filament\Resources\TournamentRegulationSettings\Pages;

use App\Filament\Resources\TournamentRegulationSettings\TournamentRegulationSettingResource;
use App\Services\AdminNotifier;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTournamentRegulationSetting extends EditRecord
{
    protected static string $resource = TournamentRegulationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AdminNotifier::notifyAction(
                DeleteAction::make(),
                $this,
                'eliminó',
                ['discipline.name'],
                'Configuración reglamentaria',
            ),
        ];
    }

    protected function afterSave(): void
    {
        AdminNotifier::send($this, $this->record, 'actualizó', ['discipline.name'], 'Configuración reglamentaria');
    }
}
