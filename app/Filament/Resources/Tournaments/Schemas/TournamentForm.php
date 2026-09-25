<?php

namespace App\Filament\Resources\Tournaments\Schemas;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Category;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\Tournament;
use App\Models\TournamentType;
use App\Services\TournamentRegulationService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

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
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('tournament_type_id', null);
                                        $set('categories', []);
                                    }),

                                Select::make('venue_id')
                                    ->label('Club organizador')
                                    ->options(fn () => Club::orderBy('name')->pluck('name', 'id'))
                                    ->columnSpan(4)
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('manual_route_checks', []))
                                    ->native(false),

                                Select::make('tournament_type_id')
                                    ->relationship(
                                        name: 'type',
                                        titleAttribute: 'name',
                                        modifyQueryUsing: fn ($query, Get $get) => $query
                                            ->where('discipline_id', $get('discipline_id'))
                                    )
                                    ->label('Tipo de torneo')
                                    ->columnSpan(3)
                                    ->disabled(fn (Get $get): bool => ! $get('discipline_id'))
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('manual_route_checks', [])),

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
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $set('end_date', $state);
                                        $set('manual_route_checks', []);
                                    })
                                    ->minutesStep(30),

                                DatePicker::make('end_date')
                                    ->label('Fecha fin')
                                    ->columnSpan(3)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn (Set $set) => $set('manual_route_checks', [])),

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
                                    ->options(fn (Get $get) => Category::query()
                                        ->where('discipline_id', $get('discipline_id'))
                                        ->orderBy('order')
                                        ->pluck('name', 'id'))
                                    ->disabled(fn (Get $get): bool => ! $get('discipline_id'))
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('manual_route_checks', []))
                                    ->required(),

                                Toggle::make('is_payment_enabled')
                                    ->label('Requiere Pago')
                                    ->columnSpan(4)
                                    ->inline(false)
                                    ->onColor('success')
                                    ->offColor('danger'),

                                Toggle::make('regulatory_override')
                                    ->label('Autorizar excepción reglamentaria')
                                    ->helperText('Solo para casos de fuerza mayor. La autorización y todos los incumplimientos quedan auditados.')
                                    ->visible(fn (): bool => Auth::user()?->hasRole('super-admin') ?? false)
                                    ->live()
                                    ->default(false)
                                    ->columnSpan(4),

                                Textarea::make('regulatory_override_reason')
                                    ->label('Motivo de fuerza mayor')
                                    ->visible(fn (Get $get): bool => (Auth::user()?->hasRole('super-admin') ?? false)
                                        && (bool) $get('regulatory_override'))
                                    ->required(fn (Get $get): bool => (bool) $get('regulatory_override'))
                                    ->maxLength(2000)
                                    ->columnSpan(8),
                            ])->disabled(fn () => ! Auth::user()->can('EditField')),

                        Tab::make('Verificación de distancias')
                            ->visible(function (Get $get): bool {
                                $typeId = $get('tournament_type_id');

                                return $typeId && ! (bool) TournamentType::find($typeId)?->is_official;
                            })
                            ->schema([
                                Repeater::make('manual_route_checks')
                                    ->label('Distancias verificadas en Google Maps')
                                    ->helperText('Agregá una fila por cada torneo coincidente indicado por la validación. Abrí la ruta, copiá la distancia mostrada y adjuntá una captura o PDF como comprobante.')
                                    ->defaultItems(0)
                                    ->collapsible()
                                    ->columns(12)
                                    ->schema([
                                        Select::make('conflicting_tournament_id')
                                            ->label('Torneo coincidente')
                                            ->options(fn (Get $get): array => Tournament::query()
                                                ->where('discipline_id', $get('../../discipline_id'))
                                                ->whereNotIn('status', ['draft', 'cancelled'])
                                                ->whereHas('type', fn ($query) => $query->where('is_official', false))
                                                ->orderBy('start_date')
                                                ->pluck('name', 'id')
                                                ->all())
                                            ->searchable()
                                            ->preload()
                                            ->distinct()
                                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                            ->required()
                                            ->live()
                                            ->columnSpan(5),
                                        Placeholder::make('route_link')
                                            ->label('Consulta')
                                            ->content(function (Get $get): HtmlString|string {
                                                $origin = Club::find($get('../../venue_id'));
                                                $existing = Tournament::with('venue.city.state.country')
                                                    ->find($get('conflicting_tournament_id'));

                                                if (! $origin || ! $existing?->venue) {
                                                    return 'Seleccioná el club y el torneo coincidente.';
                                                }

                                                $url = app(TournamentRegulationService::class)
                                                    ->googleMapsUrlForClubs($origin, $existing->venue);

                                                return new HtmlString('<a href="'.e($url).'" target="_blank" rel="noopener" class="font-semibold text-primary-600 underline">Abrir ruta en Google Maps</a>');
                                            })
                                            ->columnSpan(3),
                                        TextInput::make('distance_km')
                                            ->label('Distancia mostrada (km)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->step(0.1)
                                            ->suffix('km')
                                            ->required()
                                            ->columnSpan(4),
                                        FileUpload::make('evidence_path')
                                            ->label('Comprobante de Google Maps')
                                            ->helperText('Captura de pantalla o archivo PDF donde pueda verse la distancia.')
                                            ->disk('public_path')
                                            ->directory('tournament-regulation-evidence')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                                            ->maxSize(5120)
                                            ->downloadable()
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ])->disabled(fn (): bool => ! (Auth::user()?->can('EditField') ?? false)),

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
                                                $disciplineId = $get('../../discipline_id');

                                                return empty($enabled)
                                                    ? Category::where('discipline_id', $disciplineId)->pluck('name', 'id')
                                                    : Category::whereIn('id', $enabled)->pluck('name', 'id');
                                            }),

                                        TextInput::make('price')
                                            ->label('Precio')
                                            ->columnSpan(3)
                                            ->numeric(),
                                    ]),
                            ])->disabled(fn (Get $get): bool => ! (bool) $get('registration_enabled')
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
                                            ->label('Máx. inscripciones')
                                            ->columnSpan(1)
                                            ->numeric(),

                                        Toggle::make('is_active')
                                            ->label('Activo')
                                            ->inline(false)
                                            ->onColor('success')
                                            ->offColor('danger')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->disabled(fn (Get $get): bool => ! (bool) $get('registration_enabled')
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
