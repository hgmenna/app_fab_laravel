<?php

namespace App\Filament\Resources\Players\Schemas;

use App\Filament\Resources\Clubs\Schemas\ClubForm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PlayerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->heading('Información del jugador')
                    ->columns(6)
                    ->schema([
                        TextInput::make('first_name')
                            ->label('Nombre')
                            ->columnSpan(3)
                            ->extraAttributes(['style' => 'text-transform: uppercase'])
                            ->required(),
                        TextInput::make('last_name')
                            ->label('Apellido')
                            ->columnSpan(3)
                            ->extraAttributes(['style' => 'text-transform: uppercase'])
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->columnSpan(3)
                            ->default(null),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->columnSpan(3)
                            ->tel()
                            ->default(null),
                        TextInput::make('document_number')
                            ->label('Número de documento')
                            ->columnSpan(3)
                            ->default(null),
                        TextInput::make('document_type')
                            ->label('Tipo de documento')
                            ->columnSpan(3)
                            ->default(null),
                        TextInput::make('nationality')
                            ->label('Nacionalidad')
                            ->columnSpan(2)
                            ->default(null),
                        DatePicker::make('birth_date')
                            ->label('Fecha de nacimiento')
                            ->columnSpan(2),
                        TextInput::make('gender')
                            ->columnSpan(1)
                            ->default(null),
                    ]),
                Section::make()
                    ->heading('Información Federativa')
                    ->columns(6)
                    ->schema([
                        Select::make('club_id')
                            ->relationship('club', 'name')
                            ->label('Club')
                            ->columnSpan(6)
                            ->preload()
                            ->live()
                            ->createOptionForm(fn (Schema $schema) 
                                => ClubForm::configure($schema))
                            ->required()
                            ->searchable(),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->label('Categoría')
                            ->columnSpan(4)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->inline(false)
                            ->columnSpan(1)
                            ->required(),
                        Toggle::make('is_enabled_to_compete')
                            ->label('Habilitado')
                            ->inline(false)
                            ->columnSpan(1)
                            ->disabled(fn () => !Auth::user()->can('EditField')),
                         FileUpload::make('photo_path')
                            ->label('Foto del jugador')
                            ->columnSpan(3)
                            ->image() // valida que sea imagen
                            ->disk('public_path')
                            ->directory('players') // carpeta donde se guarda
                            ->visibility('public') // permite mostrarlo
                            ->imageEditor() // opcional: editor integrado
                            ->previewable(true) // muestra la miniatura
                            ->default(null),
                        Textarea::make('notes')
                            ->default(null)
                            ->columnSpan(3),
                    ]),
            ]);
    }
}
