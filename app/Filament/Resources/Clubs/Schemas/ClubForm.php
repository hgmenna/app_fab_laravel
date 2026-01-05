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
                        ->options(function (callable $get) {
                            $countryId = $get('country_id');
                            if (!$countryId) {
                                return [];
                            }
                            return \App\Models\State::where('country_id', $countryId)
                                ->pluck('name', 'id');
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('city_id', null);
                            $set('state', null);
                            $set('federation_id', null);
                        })->reactive(),
                    TextInput::make('city.state.name')
                        ->label('Provincia')
                        ->disabled()
                        ->default(null)
                        ->afterStateUpdated(function ($state, callable $set) {
                            $city = \App\Models\State::find($state);
                            if ($city) {
                                $set('federation_id', $city->state->federation->name);
                            } else {
                                $set('federation_id', null);
                            }
                        })->reactive(),
                    Select::make('city_id')
                        ->relationship('city', 'name')
                        ->required()
                        ->label('Ciudad')
                        ->searchable()
                        ->live()
                         ->options(function (callable $get) {
                            $stateId = $get('state_id');

                            if (!$stateId) {
                                return [];
                            }

                            return \App\Models\City::where('state_id', $stateId)
                                ->pluck('name', 'id');
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            $city = \App\Models\City::find($state);
                            if ($city) {
                                $set('state', $city->state->name);
                            } else {
                                $set('state', null);
                            }
                        })->reactive(),
                    TextInput::make('city.state.federation.name')
                        ->label('Federación')
                        ->disabled()
                        ->default(null),
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
