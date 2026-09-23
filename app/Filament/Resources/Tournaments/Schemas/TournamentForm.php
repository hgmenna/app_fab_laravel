<?php

namespace App\Filament\Resources\Tournaments\Schemas;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Category;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\TournamentType;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

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

                        Select::make('discipline_id')
                            ->label('Disciplina')
                            ->options(fn () => Discipline::orderBy('name')->pluck('name', 'id'))
                            ->columnSpan(4)
                            ->searchable(),
        
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

                        Toggle::make('registration_enabled')
                            ->label('Inscripción abierta')
                            ->helperText('Habilita la inscripción pública durante el período indicado.')
                            ->columnSpan(3)
                            ->inline(false)
                            ->live()
                            ->default(false)
                            ->afterStateUpdated(function (?bool $state, Set $set): void {
                                if (! $state) {
                                    $set('registration_open_at', null);
                                    $set('registration_close_at', null);
                                }
                            }),

                        DatePicker::make('registration_open_at')
                            ->label('Apertura de inscripción')
                            ->columnSpan(3)
                            ->visible(fn (Get $get): bool => (bool) $get('registration_enabled'))
                            ->required(fn (Get $get): bool => (bool) $get('registration_enabled'))
                            ->beforeOrEqual('registration_close_at'),

                        DatePicker::make('registration_close_at')
                            ->label('Cierre de inscripción')
                            ->columnSpan(3)
                            ->visible(fn (Get $get): bool => (bool) $get('registration_enabled'))
                            ->required(fn (Get $get): bool => (bool) $get('registration_enabled'))
                            ->afterOrEqual('registration_open_at')
                            ->beforeOrEqual('start_date'),
        
                        CheckboxList::make('categories')
                            ->label('Categorías habilitadas')
                            ->columnSpan(8)
                            ->columns(3)
                            ->options(Category::pluck('name', 'id'))
                            ->required(),
        
                            
                            Toggle::make('is_payment_enabled')
                            ->label('Requiere Pago')
                            ->columnSpan(4)
                            ->inline(false)
                            ->onColor('success')
                            ->offColor('danger'),
                    ])->disabled(fn () => !Auth::user()->can('EditField')),

                    Tab::make('Precios por categoria')
                    // ───────────────────────────────── Precios por categoría
                        ->schema([
                            Repeater::make('categoryPrices')
                                ->label('Precios por Categoria')
                                ->helperText('Opcional. Agregá filas solamente cuando el torneo tenga precios por categoría.')
                                ->defaultItems(0)
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
                                        }),
        
                                    TextInput::make('price')
                                        ->label('Precio')
                                        ->columnSpan(3)
                                        ->numeric(),
                                ])
                        ])->disabled(fn (Get $get): bool =>
                            ! (bool) $get('registration_enabled')
                            || ! (Auth::user()?->can('EditField') ?? false)
                        ),

                    Tab::make('Horarios')

                    // ───────────────────────────────── Horarios
                        ->schema([
                            Repeater::make('slots')
                                ->label('Horarios')
                                ->helperText('Opcional. Agregá horarios solamente cuando el torneo los necesite.')
                                ->defaultItems(0)
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
                                        ->native(false)
                                        ->minutesStep(30)
                                        ->secondsStep(60),
        
                                    TextInput::make('max_players')
                                        ->label('Máx. jugadores')
                                        ->columnSpan(1)
                                        ->numeric(),

                                    Toggle::make('is_active')
                                        ->label('Activo')
                                        ->inline(false)
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->columnSpan(1),
                                ])
                            ])
                        ->disabled(fn (Get $get): bool =>
                            ! (bool) $get('registration_enabled')
                            || ! (Auth::user()?->can('EditField') ?? false)
                        ),

                ])->disabled(fn () => ! Auth::user()->can('EditField')),

        ]);
    }


    public static function getResource(): string
    {
        return TournamentResource::class;
    }
}
