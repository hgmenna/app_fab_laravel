<?php
namespace App\Filament\Resources\TournamentRegulationSettings\Pages;
use App\Filament\Resources\TournamentRegulationSettings\TournamentRegulationSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListTournamentRegulationSettings extends ListRecords
{
    protected static string $resource = TournamentRegulationSettingResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
