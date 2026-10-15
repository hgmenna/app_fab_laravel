<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Services\AdminNotifier;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditPlayer extends EditRecord
{
    protected static string $resource = PlayerResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();
        $disciplineId = $data['discipline_id'] ?? $this->record->discipline_id;

        if ($user?->hasClubScopeForDiscipline($disciplineId, 'Update:Player')
            && ! in_array((int) ($data['club_id'] ?? 0), $user->allowedClubIds('Update:Player', $disciplineId), true)) {
            throw ValidationException::withMessages([
                'data.club_id' => 'Solo podés modificar jugadores del club asignado a tu usuario.',
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            AdminNotifier::notifyAction(DeleteAction::make(), $this, 'eliminó', ['last_name', 'first_name']),
            AdminNotifier::notifyAction(ForceDeleteAction::make(), $this, 'eliminó definitivamente', ['last_name', 'first_name']),
            AdminNotifier::notifyAction(RestoreAction::make(), $this, 'restauró', ['last_name', 'first_name']),
        ];
    }

    protected function afterSave(): void
    {
        // $this->record es el modelo recién creado
        AdminNotifier::send($this, $this->record, 'actualizó', ['last_name', 'first_name']);
    }
}
