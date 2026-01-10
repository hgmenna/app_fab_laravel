<?php

namespace App\Filament\Resources\Federations\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FederationForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            TextInput::make('name')
                ->required(),
            TextInput::make('short_name')
                ->default(null),
            TextInput::make('mail_contact')
                ->default(null),
            TextInput::make('website')
                ->default(null),
            TextInput::make('phone')
                ->tel()
                ->default(null),
            FileUpload::make('logo_path')
                ->label('Logo')
                ->image() // valida que sea imagen
                ->directory('logos/federations') // carpeta donde se guarda
                ->visibility('public') // permite mostrarlo
                ->imageEditor() // opcional: editor integrado
                ->previewable(true) // muestra la miniatura
                ->downloadable() // permite descargar
                ->openable() // permite abrir en nueva pestaña
                ->required(false),
        ]);
    }
}
