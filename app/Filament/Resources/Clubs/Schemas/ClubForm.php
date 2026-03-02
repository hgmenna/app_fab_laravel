<?php

namespace App\Filament\Resources\Clubs\Schemas;

use App\Filament\Resources\Cities\Schemas\CityForm;
use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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
                        ->columnSpan(2)
                        ->required(),
                    TextInput::make('short_name')
                        ->label('Sigla')
                        ->columnSpan(1)
                        ->default(null),
                    TextInput::make('tax_id')
                        ->default(null)
                        ->maxLength(14)
                        ->mask('99-999999999-9', true)
                        ->label('CUIT')
                        ->columnSpan(1),
            ]),

            Section::make()
                ->heading('Información de contacto')
                    ->columns(6)
                ->schema([
                    TextInput::make('phone')
                        ->label('Teléfono')
                        ->columnSpan(2)
                        ->tel()
                        ->default(null),
                    TextInput::make('mail_contact')
                        ->email()
                        ->default(null)
                        ->columnSpan(4)
                        ->label('Correo electronico'),
                    TextInput::make('website')
                        ->url()
                        ->default(null)
                        ->label('Sitio web')
                        ->columnSpan(3),
                    TextInput::make('contact_person')
                        ->default(null)
                        ->label('Persona de contacto')
                        ->columnSpan(3),
            ]),
            Section::make()
                ->heading('Domicilio')
                ->columns([
                    'sm' => 1,
                    'md' => 1,
                    'lg' => 4
                ])
                ->schema([
                    TextInput::make('address')
                        ->label('Dirección')
                        ->columnSpan(4)
                        ->default(null),
                    Select::make('country_id')
                        ->label('País')
                        ->columnSpan(2)
                        ->options(Country::pluck('name', 'id'))
                        ->default(function () {
                            return Country::where('name', 'Argentina')->value('id');
                        })
                        ->getOptionLabelUsing(fn ($value) => \App\Models\Country::find($value)?->name)
                        ->searchable()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(function ($state, callable $set, $record) {
                            if($record) {
                                $record->loadMissing('city.state.country');
                                $set('country_id', $record->city?->state?->country_id);
                            }
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('state_id', null);
                            $set('city_id', null);
                            $set('federation_name', null);
                        }),
                    Select::make('state_id')
                        ->label('Provincia')
                        ->columnSpan(2)
                        ->options(function (callable $get) {
                            $countryId = $get('country_id');

                            if (!$countryId) {
                                return [];
                            }

                            return State::where('country_id', $countryId)
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value) => State::find($value)?->name)
                        ->searchable()
                        ->live()
                        ->dehydrated(false) // no se guarda en clubs
                        ->afterStateHydrated(function ($state, callable $set, $record) {
                            // reconstruir state desde city
                            if ($record?->city?->state_id) {
                                $set('state_id', $record->city->state_id);
                            }
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            // resetear ciudad
                            $set('city_id', null);

                            // cargar federación desde state
                            $stateModel = State::with('federation')->find($state);
                            $set('federation_name', $stateModel?->federation?->name);
                        }),
                    Select::make('city_id')
                        ->label('Ciudad')
                        ->columnSpan([
                            'lg' => 2,
                            'md' => 3,
                            'sm' => 3
                        ])
                        ->searchable()
                        ->live()
                         ->options(function (callable $get) {
                            $stateId = $get('state_id');

                            if (!$stateId) return [];

                            return City::where('state_id', $stateId)
                                ->pluck('name', 'id')
                                ->toarray();
                        })
                        ->createOptionForm(fn (Schema $schema) => CityForm::configure($schema))
                        ->required(),
                    TextInput::make('federation_name')
                        ->label('Federación')
                        ->columnSpan(2)
                        ->disabled()
                        ->dehydrated(false),
                ]),
            Section::make()
                ->columns(4)
                ->schema([
                    Textarea::make('notes')
                        ->label('Observaciones')
                        ->columnSpan(2)
                        ->default(null)
                        ->columnSpanFull(),
                    FileUpload::make('logo_path')
                        ->default(null)
                        ->label('Logo')
                        ->columnSpan(3)
                        ->image() // valida que sea imagen
                        ->directory('logos/clubs') // carpeta donde se guarda
                        ->visibility('public') // permite mostrarlo
                        ->imageEditor() // opcional: editor integrado
                        ->previewable(true), // muestra la miniatura,
                    Toggle::make('is_active')
                        ->label('Activo')
                        ->columnSpan(1)
                        ->default(true)
                        ->required(),
                    
                ]),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::getFormSchema());
    }
}
