<?php

namespace App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\Pages;

use App\Filament\Resources\Tournaments\Resources\TournamentRegistrations\TournamentRegistrationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Concerns\HasTabs;
use Filament\Resources\Pages\EditRecord;

class EditTournamentRegistration extends EditRecord
{
    use HasTabs;
    protected static string $resource = TournamentRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

}
