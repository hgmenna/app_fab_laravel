<?php

namespace App\Filament\Resources\Clubs\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Models\Country;


class ClubForm
{
    public static function getFormSchema(): array
    {
        return [
            Section::make()
                ->heading('Información básica')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required(),
                    TextInput::make('short_name')
                        ->label('Sigla')
                        ->default(null),
                    TextInput::make('tax_id')
                        ->default(null)
                        ->maxLength(14)
                        ->mask('99-999999999-9', true)
                        ->label('CUIT'),
            ]),

            Section::make()
                ->heading('Información de contacto')
                    ->columns(2)
                ->schema([
                    TextInput::make('phone')
                        ->label('Teléfono')
                        ->tel()
                        ->default(null),
                    TextInput::make('mail_contact')
                        ->email()
                        ->default(null)
                        ->label('Correo electronico'),
                    TextInput::make('website')
                        ->url()
                        ->default(null)
                        ->label('Sitio web'),
                    TextInput::make('contact_person')
                        ->default(null)
                        ->label('Persona de contacto'),
            ]),
            Section::make()
                ->heading('Domicilio')
                ->columns(2)
                ->schema([
                    TextInput::make('address')
                        ->label('Dirección')
                        ->default(null),
                    Select::make('country_id')
                        ->required()
                        ->label('País')
                        ->searchable()
                        ->live()
                        ->dehydrated(false)
                        ->options(Country::pluck('name', 'id')->toArray())
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('city_id', null);
                            $set('state_id', null);
                            $set('federation_name', null);
                        }),
                    Select::make('state_id')
                        ->label('Provincia')
                        ->options(function (callable $get) {
                            $countryId = $get('country_id');

                            if (!$countryId) return [];

                            return \App\Models\State::where('country_id', $countryId)
                                ->pluck('name', 'id')
                                ->toarray();
                        })
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('city_id', null);

                            // Cargar Federacion automáticamente

                                $stateModel = \App\Models\State::with('federation')->find($state);
                                $set('federation_name', $stateModel ? $stateModel->federation->name : null);

                        }),
                    Select::make('city_id')
                        ->label('Ciudad')
                        ->searchable()
                        ->live()
                         ->options(function (callable $get) {
                            $stateId = $get('state_id');

                            if (!$stateId) return [];

                            return \App\Models\City::where('state_id', $stateId)
                                ->pluck('name', 'id')
                                ->toarray();
                        })
                        ->required(),
                    TextInput::make('federation_name')
                        ->label('Federación')
                        ->disabled()
                        ->dehydrated(false),
                ]),
            FileUpload::make('logo_path')
                ->default(null)
                ->label('Logo')
                ->image() // valida que sea imagen
                ->directory('logos/clubs') // carpeta donde se guarda
                ->visibility('public') // permite mostrarlo
                ->imageEditor() // opcional: editor integrado
                ->previewable(true), // muestra la miniatura,
            Textarea::make('notes')
                ->default(null)
                ->columnSpanFull(),
            Toggle::make('is_active')
                ->required(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::getFormSchema());
    }
}
