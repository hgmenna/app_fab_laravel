<?php
namespace App\Filament\Resources\TournamentRegulationSettings\Pages;
use App\Filament\Resources\TournamentRegulationSettings\TournamentRegulationSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditTournamentRegulationSetting extends EditRecord
{
    protected static string $resource = TournamentRegulationSettingResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}
