<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use Filament\Resources\Pages\CreateRecord;
use App\Services\AdminNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class CreatePlayer extends CreateRecord
{
    protected static string $resource = PlayerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $disciplineId = $data['discipline_id'] ?? null;

        if ($user?->hasClubScopeForDiscipline($disciplineId, 'Create:Player')
            && ! in_array((int) ($data['club_id'] ?? 0), $user->allowedClubIds('Create:Player', $disciplineId), true)) {
            throw ValidationException::withMessages([
                'data.club_id' => 'Solo podés crear jugadores para el club asignado a tu usuario.',
            ]);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // $this->record es el modelo recién creado
        AdminNotifier::send($this, $this->record, 'creó', ['last_name', 'first_name']);
    }
}
