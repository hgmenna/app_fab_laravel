<?php

namespace App\Filament\Resources\Tournaments\Schemas;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Category;
use App\Models\Player;
use App\Models\TournamentInstance;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\Club;
use Filament\Forms\Components\DateTimePicker;

class TournamentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            // ───────────────────────────────── Datos del torneo
            Section::make('Datos del torneo')
                ->columns(6)
                ->schema([

                TextInput::make('name')
                    ->label('Nombre del torneo')
                    ->columnSpan(3)
                    ->required()
                    ->reactive(),

                Select::make('venue_id')
                    ->label('Club organizador')
                    ->options(fn () => Club::orderBy('name')->pluck('name', 'id'))
                    ->columnSpan(3)
                    ->searchable()
                    ->required()
                    ->native(false),

                DatePicker::make('start_date')
                    ->label('Fecha inicio')
                    ->columnSpan(2)
                    ->required()
                    ->reactive()
                    ->minutesStep(30),

                DatePicker::make('end_date')
                    ->label('Fecha fin')
                    ->columnSpan(2)
                    ->required()
                    ->default(fn (callable $get) => $get('start_day'))
                    ->minDate(fn (callable $get) => $get('start_day'))
                    ->reactive(),


                Select::make('tournament_type_id')
                    ->relationship(name: 'type', titleAttribute: 'name')
                    ->label('Tipo de torneo')
                    ->columnSpan(2)
                    ->required(),

                Select::make('categories')
                    ->label('Categorías habilitadas')
                    ->columnSpan(2)
                    ->multiple()
                    ->options(Category::pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                Toggle::make('is_open_for_registration')
                    ->label('Inscripción habilitada')
                    ->columnSpan(2),

                Toggle::make('payment_enabled')
                    ->label('Pago online habilitado')
                    ->columnSpan(2),
            ]),

            // ───────────────────────────────── Horarios
            Section::make('Horarios')
            ->schema([
                Repeater::make('slots')
                        ->columns(6)
                        ->relationship('slots')
                        ->schema([
                            DateTimePicker::make('starts_at')
                                ->label('Inicio')
                                ->columnSpan(2)
                                ->required(),

                            TextInput::make('max_players')
                                ->label('Máx. jugadores')
                                ->columnSpan(2)
                                ->numeric()
                                ->required(),
                            Toggle::make('is_active')
                                ->label('Activo')
                                ->columnSpan(2),
                        ])
                ]),

            // ───────────────────────────────── Precios por categoría
            Section::make('Precios por categoría')
                ->schema([
                    Repeater::make('categoryPrices')
                        ->columns(6)
                        ->relationship('categoryPrices')
                        ->schema([
                            Select::make('category_id')
                                ->label('Categoría')
                                ->columnSpan(3)
                                ->options(function (callable $get) {
                                    $enabled = $get('../../categories') ?? [];

                                    return empty($enabled)
                                        ? Category::pluck('name', 'id')
                                        : Category::whereIn('id', $enabled)->pluck('name', 'id');
                                })
                                ->required(),

                            TextInput::make('price')
                                ->label('Precio')
                                ->columnSpan(3)
                                ->numeric()
                                ->required(),
                            ])
                ]),

        ]);
    }


    public static function getResource(): string
    {
        return TournamentResource::class;
    }
}
