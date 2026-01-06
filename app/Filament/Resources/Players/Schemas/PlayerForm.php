<?php

namespace App\Filament\Resources\Players\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;

class PlayerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading('Información del jugador')
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Nombre')
                            ->extraAttributes(['style' => 'text-transform: uppercase'])
                            ->required(),
                        TextInput::make('last_name')
                            ->label('Apellido')
                            ->extraAttributes(['style' => 'text-transform: uppercase'])
                            ->required(),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->default(null),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->default(null),
                        TextInput::make('document_number')
                            ->label('Número de documento')
                            ->default(null),
                        TextInput::make('document_type')
                            ->label('Tipo de documento')
                            ->default(null),
                        TextInput::make('nationality')
                            ->default(null),
                        DatePicker::make('birth_date')
                            ->label('Fecha de nacimiento'),
                        TextInput::make('gender')
                            ->default(null),
                    ]),
                Section::make()
                    ->heading('Información Federativa')
                    ->schema([
                        Select::make('club_id')
                            ->relationship('club', 'name')
                            ->label('Club')
                            ->required(),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->label('Categoría')
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->required(),
                        Toggle::make('is_enabled_to_compete')
                            ->label('Habilitado para competir')
                            ->required(),
                         FileUpload::make('photo_path')
                            ->label('Foto del jugador')
                            ->image() // valida que sea imagen
                            ->directory('logos/players') // carpeta donde se guarda
                            ->visibility('public') // permite mostrarlo
                            ->imageEditor() // opcional: editor integrado
                            ->previewable(true) // muestra la miniatura
                            ->default(null),
                        Textarea::make('notes')
                            ->default(null)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
