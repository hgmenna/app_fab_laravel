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


class TournamentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            //
            Tabs::make()
                ->tabs([
                    // ───────────────────────────────── Datos del torneo
                    Tab::make('Datos del torneo')
                        ->schema([
                            Section::make('Información básica')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Nombre del torneo')
                                        ->required(),

                                    DatePicker::make('start_date')
                                        ->label('Fecha inicio')
                                        ->required(),

                                    DatePicker::make('end_date')
                                        ->label('Fecha fin')
                                        ->required(),

                                    Select::make('venue_id')
                                        ->relationship(name: 'venue', titleAttribute: 'name')
                                        ->label('Club organizador')
                                        ->searchable()
                                        ->required(),

                                    Select::make('tournament_type_id')
                                        ->relationship(name: 'type', titleAttribute: 'name')
                                        ->label('Tipo de torneo')
                                        ->required(),

                                    Select::make('categories')
                                        ->label('Categorías habilitadas')
                                        ->multiple()
                                        ->options(Category::pluck('name', 'id'))
                                        ->searchable()
                                        ->required(),

                                    Toggle::make('is_open_for_registration')
                                        ->label('Inscripción habilitada'),

                                    Toggle::make('payment_enabled')
                                        ->label('Pago online habilitado'),
                                ])
                                ->columns(2)
                                ->columnSpanFull(),

                            Section::make('Aspecto y notas')
                                ->schema([
                                    FileUpload::make('logo_path')
                                        ->label('Logo')
                                        ->image()
                                        ->directory('tournaments/logos'),

                                    Textarea::make('notes')
                                        ->label('Notas')
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ───────────────────────────────── Horarios
                    Tab::make('Horarios')
                        ->schema([
                            Repeater::make('slots')
                                ->relationship('slots')
                                ->schema([
                                    TimePicker::make('starts_at')
                                        ->label('Inicio')
                                        ->required(),

                                    TextInput::make('max_players')
                                        ->label('Máx. jugadores')
                                        ->numeric()
                                        ->required(),
                                    Toggle::make('is_active')
                                        ->label('Activo'),
                                ])
                                ->columnSpanFull(),
                        ]),

                    // ───────────────────────────────── Precios por categoría
                    Tab::make('Precios por categoría')
                        ->schema([
                            Repeater::make('categoryPrices')
                                ->relationship('categoryPrices')
                                ->schema([
                                    Select::make('category_id')
                                        ->label('Categoría')
                                        ->options(function (callable $get) {
                                            $enabled = $get('../../categories') ?? [];

                                            return empty($enabled)
                                                ? Category::pluck('name', 'id')
                                                : Category::whereIn('id', $enabled)->pluck('name', 'id');
                                        })
                                        ->required(),

                                    TextInput::make('price')
                                        ->label('Precio')
                                        ->numeric()
                                        ->required(),
                                    ])
                                    ->columnSpanFull(),
                        ]),

                    // ───────────────────────────────── Inscripciones
                    Tab::make('Inscripciones')
                        ->schema([
                            Repeater::make('registrations')
                                ->relationship('registrations')
                                ->schema([

                                    // Jugador filtrado por categorías habilitadas
                                    Select::make('player_id')
                                        ->label('Jugador')
                                        ->options(function (callable $get) {
                                            $enabled = $get('../../categories') ?? [];

                                            $query = Player::query()
                                                ->with(['club.city.state.federation', 'category'])
                                                ->orderBy('last_name');

                                            if (! empty($enabled)) {
                                                $query->whereIn('category_id', $enabled);
                                            }

                                            return $query->get()->pluck('full_name', 'id');
                                        })
                                        ->searchable()
                                        ->reactive()
                                        ->required(),

                                    // Club (solo lectura)
                                    TextInput::make('club_name')
                                        ->label('Club')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn ($record) =>
                                            $record?->player?->club_name ?? '-'
                                        ),

                                    // Categoría (solo lectura)
                                    TextInput::make('category_name')
                                        ->label('Categoría')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn ($record) =>
                                            $record?->player?->category_name ?? '-'
                                        ),

                                    // Federación (solo lectura)
                                    TextInput::make('federation')
                                        ->label('Federación')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn ($record) =>
                                            $record?->player?->federation_short_name ?? '-'
                                        ),

                                    // Horario
                                    Select::make('slot_id')
                                        ->label('Horario')
                                        ->relationship(name: 'slot', titleAttribute: 'starts_at')
                                        ->required(),

                                    // Estado de pago
                                    Select::make('payment_status')
                                        ->label('Pago')
                                        ->options([
                                            'pending' => 'Pendiente',
                                            'paid'    => 'Pagado',
                                            'failed'  => 'Fallido',
                                        ])
                                        ->required(),
                                        ])
                                        ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),

                    // ───────────────────────────────── Resultados
                    Tab::make('Resultados')
                        ->label('Resultados del torneo')
                        ->schema([
                            Repeater::make('registrations')
                                ->relationship('registrations')
                                ->schema([

                                    TextInput::make('player_name')
                                        ->label('Jugador')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn ($record) =>
                                            $record?->player?->full_name ?? '-'
                                        )
                                        ->columnSpan(3),

                                    TextInput::make('club_name')
                                        ->label('Club')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn ($record) =>
                                            $record?->player?->club_name ?? '-'
                                        )
                                        ->columnSpan(3),

                                    TextInput::make('category_name')
                                        ->label('Categoría')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn ($record) =>
                                            $record?->player?->category_name ?? '-'
                                        )
                                        ->columnSpan(2),

                                    TextInput::make('federation')
                                        ->label('Federación')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->formatStateUsing(fn ($record) =>
                                            $record?->player?->federation_short_name ?? '-'
                                        )
                                        ->columnSpan(2),

                                    // Instancia de puntaje (tabla maestra)
                                    Select::make('tournaments_instance_id')
                                        ->label('Instancia / Resultado')
                                        ->options(function () {
                                            return TournamentInstance::orderBy('instance')
                                                ->get()
                                                ->mapWithKeys(fn ($i) => [
                                                    $i->id => "{$i->instance} - {$i->description} ({$i->points} pts base)",
                                                ]);
                                        })
                                        ->columnSpan(4)
                                        ->searchable()
                                        ->reactive()
                                        ->required(),

                                    // Puntos finales (calculados por el modelo)
                                    TextInput::make('points')
                                        ->label('Puntos')
                                        ->columnSpan(2)
                                        ->disabled(),
                                ])
                                ->grid(6)
                                ->columnSpanFull()
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false),
                        ])
                        ->columnSpanFull(),
            ])
            ->columnSpanFull(),
        ]);
    }


    public static function getResource(): string
    {
        return TournamentResource::class;
    }
}
