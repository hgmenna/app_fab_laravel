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
use App\Models\TournamentType;

class TournamentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            // ───────────────────────────────── Datos del torneo
            Tabs::make('Tabs')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Datos del Torneo')
                        ->columns(12)
                        ->columnSpanFull()
                        ->schema([
        
                        TextInput::make('name')
                            ->label('Nombre del torneo')
                            ->columnSpan(5)
                            ->required()
                            ->reactive(),
        
                        Select::make('venue_id')
                            ->label('Club organizador')
                            ->options(fn () => Club::orderBy('name')->pluck('name', 'id'))
                            ->columnSpan(4)
                            ->searchable()
                            ->required()
                            ->native(false),

                        Select::make('tournament_type_id')
                            ->relationship(name: 'type', titleAttribute: 'name')
                            ->label('Tipo de torneo')
                            ->columnSpan(3)
                            ->required(),

                        TextInput::make('stage_number')
                            ->label('Etapa (1 a 4)')
                            ->numeric()
                            ->columnSpan(2)
                            ->minValue(1)
                            ->maxValue(4)
                            ->visible(function ($get) {
                                $typeId = $get('tournament_type_id');

                                if (! $typeId) {
                                    return false;
                                }

                                $type = TournamentType::find($typeId);

                                return $type?->affects_ranking && $type?->assigns_points;
                            })
                            ->required(function ($get) {
                                $typeId = $get('tournament_type_id');

                                if (! $typeId) {
                                    return false;
                                }

                                $type = TournamentType::find($typeId);

                                return $type?->affects_ranking && $type?->assigns_points;
                            }),

                            
                        DatePicker::make('start_date')
                            ->label('Fecha inicio')
                            ->columnSpan(3)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function($state, callable $set) {
                                $set('end_date', $state);
                            })
                            ->minutesStep(30),
        
                        DatePicker::make('end_date')
                            ->label('Fecha fin')
                            ->columnSpan(3)
                            ->required()
                            ->reactive(),
        
                            DatePicker::make('registration_open_at')
                                ->label('Apertura de Inscripcion')
                                ->columnSpan(3),
            
                            DatePicker::make('registration_close_at')
                                ->label('Cierre de Inscripcion')
                                ->columnSpan(3),
        
                        Select::make('categories')
                            ->label('Categorías habilitadas')
                            ->columnSpan(8)
                            ->multiple()
                            ->options(Category::pluck('name', 'id'))
                            ->searchable()
                            ->required(),
        
                            
                            Toggle::make('is_payment_enabled')
                            ->label('Requiere Pago')
                            ->columnSpan(4)
                            ->inline(false)
                            ->onColor('success')
                            ->offColor('danger'),
                    ]),

                    Tab::make('Precios por categoria')
                    // ───────────────────────────────── Precios por categoría
                        ->schema([
                            Repeater::make('categoryPrices')
                                ->collapsible()
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

                    Tab::make('Horarios')

                    // ───────────────────────────────── Horarios
                        ->schema([
                            Repeater::make('slots')
                                ->grid(3)
                                ->collapsible()
                                ->columns(4)
                                ->relationship('slots')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Nombre')
                                        ->columnSpan(4),

                                    DateTimePicker::make('starts_at')
                                        ->label('Inicio')
                                        ->columnSpan(2)
                                        ->required()
                                        ->native(false)
                                        ->minutesStep(30)
                                        ->secondsStep(60),
        
                                    TextInput::make('max_players')
                                        ->label('Máx. jugadores')
                                        ->columnSpan(1)
                                        ->numeric()
                                        ->required(),

                                    Toggle::make('is_active')
                                        ->label('Activo')
                                        ->inline(false)
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->columnSpan(1),
                                ])
                            ]),

                ])

        ]);
    }


    public static function getResource(): string
    {
        return TournamentResource::class;
    }
}
