<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre de Usuario')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Rol')
                    ->multiple()
                    ->preload()
                    ->searchable(),
                DateTimePicker::make('email_verified_at')
                    ->label('Fecha verificacion Mail'),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable() // Permite ver la clave mientras se escribe [6]
                    
                    // 1. Visibilidad dinámica: Solo Super-Admin o el propio Usuario
                    ->visible(function (string $operation, ?Model $record) {
                        // En la creación, el campo debe ser visible para el administrador
                        if ($operation === 'create') {
                            return true;
                        }

                        $user = Auth::user();

                        // El super-admin puede ver el campo siempre [5]
                        if ($user->name === 'super-admin') {
                            return true;
                        }

                        // El usuario solo puede ver el campo si el registro editado es el suyo [3]
                        return $record && $user->id === $record->id;
                    })

                    // 2. Solo obligatorio al crear un nuevo usuario [3]
                    ->required(fn (string $operation): bool => $operation === 'create')

                    // 3. Evita borrar la contraseña actual si el campo se deja vacío al editar
                    ->dehydrated(fn ($state) => filled($state)
                ),
            ]);
    }
}
