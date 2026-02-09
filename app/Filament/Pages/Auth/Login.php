<?php

namespace App\Filament\Pages\Auth;

use Filament\Schemas\Components\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
        ->components([
            // Campo 'name' con etiqueta directa
            TextInput::make('name') 
                ->label('Nombre de usuario')
                ->required()
                ->autocomplete()
                ->autofocus(),

            // Campo 'password' con etiqueta directa para evitar error de traducción
            TextInput::make('password')
                ->label('Contraseña') // <--- Cambiado a texto plano
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->required(),

            // Checkbox de 'remember' con etiqueta directa
            Checkbox::make('remember')
                ->label('Recordarme'), // <--- Cambiado a texto plano
        ])
        ->statePath('data');
           
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username') // Cambia 'email' por el nombre de tu columna
            ->label('Nombre de usuario')
            ->required()
            ->autocomplete();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'name' => $data['name'],
            'password' => $data['password'],
        ];
    }
}
