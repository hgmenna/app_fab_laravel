<?php

namespace App\Filament\Resources\Clubs\Pages;

use App\Filament\Resources\Clubs\ClubResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\City;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateClub extends CreateRecord
{
    protected static string $resource = ClubResource::class;
    protected static ?string $title = 'Nuevo Club';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        // 1. Verificamos si el usuario tiene el rol de Federación
        // Shield requiere el trait HasRoles en el modelo User [3]
        if ($user->hasRole('Federacion')) {
            
            /** 
             * 2. Validamos la jerarquía definida: 
             * city.state.federation.short_name 
             */
            $city = City::with('state.federation')->find($data['city_id']);

            // 3. Comprobamos si la ciudad elegida pertenece a su federación
            if (!$city || $city->state->federation->short_name !== $user->username) {
                
                // Si no hay coincidencia, lanzamos un error de validación en el formulario
                throw ValidationException::withMessages([
                    'city_id' => 'No tienes permiso para crear clubes en una ubicación que no pertenece a tu federación.',
                ]);
            }
        }

        /**
         * Si el usuario es Super Admin u otro rol, 
         * el flujo continuará normalmente.
         */
        return $data;
    }
}
