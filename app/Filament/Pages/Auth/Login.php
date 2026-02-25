<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
        ->components([
             // Usamos el helper del nombre de usuario que ya definiste abajo
            $this->getNameFormComponent(), 

            // Usamos el helper de la base para que incluya el link de "olvidé mi contraseña"
            $this->getPasswordFormComponent()
                ->label('Contraseña'), // <--- Solo cambiamos la etiqueta

            // Usamos el helper para el checkbox de recordarme
            $this->getRememberFormComponent()
                ->label('Recordarme'),
        ])
        ->statePath('data');
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name') // Cambia 'email' por el nombre de tu columna
            ->label('Nombre de usuario')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'name' => $data['name'],
            'password' => $data['password'],
        ];
    }
}
