<?php

namespace App\Filament\Resources\Tournaments\Schemas;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\Category;
use App\Models\Club;
use App\Models\Discipline;
use App\Models\DisciplineModality;
use App\Models\State;
use App\Models\Tournament;
use App\Models\TournamentType;
use App\Services\TournamentRegulationService;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
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
                                    ->options(fn () => Auth::user()?->scopeDisciplineQuery(Discipline::query(), 'Create:Tournament')->orderBy('name')->pluck('name', 'id') ?? [])
                                    ->columnSpan(4)
                                    ->searchable()
                                    ->required()
                                    ->default(fn () => Auth::user()?->defaultDisciplineId('Create:Tournament'))
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('tournament_type_id', null);
                                        $set('categories', []);
                                        $set('tournamentModalities', []);
                                        $set('manual_route_checks', []);
                                    }),

                                Select::make('venue_type')
                                    ->label('Tipo de sede')
                                    ->options([
                                        'club' => 'Club registrado',
                                        'external' => 'Sede no registrada',
                                        'unassigned' => 'Sin asignar',
                                    ])
                                    ->default('unassigned')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Set $set): void {
                                        $set('manual_route_checks', []);

                                        if ($state !== 'club') {
                                            $set('venue_id', null);
                                        }

                                        if ($state !== 'external') {
                                            $set('external_venue_name', null);
                                            $set('external_venue_address', null);
                                            $set('external_venue_city', null);
                                            $set('external_venue_state_id', null);
                                        }

                                        if ($state === 'external') {
                                            $set('non_official_logo_source', 'venue_federation');
                                        }
                                    })
                                    ->native(false)
                                    ->columnSpan(3),

                                Select::make('venue_id')
                                    ->label('Club organizador / sede')
                                    ->options(fn () => Club::orderBy('name')->pluck('name', 'id'))
                                    ->columnSpan(5)
                                    ->searchable()
                                    ->required(fn (Get $get): bool => $get('venue_type') === 'club')
                                    ->visible(fn (Get $get): bool => $get('venue_type') === 'club')
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('manual_route_checks', []))
                                    ->native(false),

                                TextInput::make('external_venue_name')
                                    ->label('Nombre de la sede')
                                    ->placeholder('Casino, sede gubernamental u otra')
                                    ->required(fn (Get $get): bool => $get('venue_type') === 'external')
                                    ->visible(fn (Get $get): bool => $get('venue_type') === 'external')
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('manual_route_checks', []))
                                    ->columnSpan(5),

                                TextInput::make('external_venue_address')
                                    ->label('Domicilio de la sede')
                                    ->required(fn (Get $get): bool => $get('venue_type') === 'external')
                                    ->visible(fn (Get $get): bool => $get('venue_type') === 'external')
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('manual_route_checks', []))
                                    ->columnSpan(4),

                                TextInput::make('external_venue_city')
                                    ->label('Localidad')
                                    ->required(fn (Get $get): bool => $get('venue_type') === 'external')
                                    ->visible(fn (Get $get): bool => $get('venue_type') === 'external')
                                    ->maxLength(255)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('manual_route_checks', []))
                                    ->columnSpan(4),

                                Select::make('external_venue_state_id')
                                    ->label('Provincia')
                                    ->options(fn () => State::orderBy('name')->pluck('name', 'id'))
                                    ->required(fn (Get $get): bool => $get('venue_type') === 'external')
                                    ->visible(fn (Get $get): bool => $get('venue_type') === 'external')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set) => $set('manual_route_checks', []))
                                    ->native(false)
                                    ->columnSpan(4),

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
                                    ->afterStateUpdated(function ($state, Set $set): void {
                                        $set('manual_route_checks', []);
                                        $type = TournamentType::find($state);

                                        if ($type && ! $type->is_official) {
                                            $set('non_official_logo_source', 'venue_federation');
                                        }
                                    }),

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

                                Hidden::make('categories')->default([]),

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
                            ])->disabled(fn () => ! Auth::user()->canGloballyOrInAnyDiscipline('EditField')),

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
                                            ->options(function (Get $get, ?Tournament $record): array {
                                                $candidate = (new Tournament)->forceFill([
                                                    'discipline_id' => $get('../../discipline_id'),
                                                    'tournament_type_id' => $get('../../tournament_type_id'),
                                                    'venue_id' => $get('../../venue_id'),
                                                    'venue_type' => $get('../../venue_type'),
                                                    'external_venue_name' => $get('../../external_venue_name'),
                                                    'external_venue_address' => $get('../../external_venue_address'),
                                                    'external_venue_city' => $get('../../external_venue_city'),
                                                    'external_venue_state_id' => $get('../../external_venue_state_id'),
                                                    'start_date' => $get('../../start_date'),
                                                    'end_date' => $get('../../end_date'),
                                                    'categories' => $get('../../categories') ?? [],
                                                    'status' => $get('../../status') ?? 'draft',
                                                ]);

                                                if ($record) {
                                                    $candidate->setAttribute('id', $record->id);
                                                    $candidate->exists = true;
                                                }

                                                return app(TournamentRegulationService::class)
                                                    ->distanceVerificationTournaments($candidate)
                                                    ->mapWithKeys(fn (Tournament $tournament): array => [
                                                        $tournament->id => implode(' · ', array_filter([
                                                            $tournament->name,
                                                            $tournament->start_date?->format('d/m/Y'),
                                                            $tournament->venueName(),
                                                        ])),
                                                    ])
                                                    ->all();
                                            })
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
                                                $candidate = (new Tournament)->forceFill([
                                                    'venue_id' => $get('../../venue_id'),
                                                    'venue_type' => $get('../../venue_type'),
                                                    'external_venue_name' => $get('../../external_venue_name'),
                                                    'external_venue_address' => $get('../../external_venue_address'),
                                                    'external_venue_city' => $get('../../external_venue_city'),
                                                    'external_venue_state_id' => $get('../../external_venue_state_id'),
                                                ]);
                                                $existing = Tournament::with([
                                                    'venue.city.state.country',
                                                    'externalVenueState.country',
                                                ])->find($get('conflicting_tournament_id'));

                                                if (! $candidate->hasAssignedVenue() || ! $existing?->hasAssignedVenue()) {
                                                    return 'Seleccioná o completá ambas sedes.';
                                                }

                                                $url = app(TournamentRegulationService::class)
                                                    ->googleMapsUrl($candidate, $existing);

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
                                            ->helperText('Capturá la ruta, hacé clic en esta área y presioná Ctrl+V. La imagen se carga directamente desde el portapapeles. También podés arrastrar o seleccionar un archivo.')
                                            ->placeholder('Pegá la captura con Ctrl+V o hacé clic para seleccionar un archivo')
                                            ->pasteable()
                                            ->disk('public_path')
                                            ->directory('tournament-regulation-evidence')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                                            ->previewable()
                                            ->openable()
                                            ->maxSize(5120)
                                            ->downloadable()
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ])->disabled(fn (): bool => ! (Auth::user()?->canGloballyOrInAnyDiscipline('EditField') ?? false)),

                        Tab::make('Publicación')
                            ->columns(12)
                            ->schema([
                                FileUpload::make('flyer_path')
                                    ->label('Flyer del torneo')
                                    ->helperText('Pegá la imagen desde el portapapeles con Ctrl+V, arrastrala o seleccionala desde el dispositivo. Formatos: JPG, PNG o WebP; máximo 10 MB.')
                                    ->placeholder('Pegá el flyer con Ctrl+V o hacé clic para seleccionar una imagen')
                                    ->pasteable()
                                    ->image()
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->maxSize(10240)
                                    ->directory('tournaments/flyers')
                                    ->disk('public_path')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->previewable()
                                    ->downloadable()
                                    ->openable()
                                    ->columnSpan(8),

                                Select::make('non_official_logo_source')
                                    ->label('Logo para la publicación')
                                    ->options(fn (Get $get): array => $get('venue_type') === 'external'
                                        ? ['venue_federation' => 'Federación de la provincia de la sede']
                                        : [
                                            'venue_club' => 'Club organizador',
                                            'venue_federation' => 'Federación del club organizador',
                                        ])
                                    ->default('venue_federation')
                                    ->helperText('La imagen se toma automáticamente del club o de su federación cuando se asigne la sede.')
                                    ->visible(function (Get $get): bool {
                                        $type = TournamentType::find($get('tournament_type_id'));

                                        return $type && ! $type->is_official;
                                    })
                                    ->required(function (Get $get): bool {
                                        $type = TournamentType::find($get('tournament_type_id'));

                                        return $type && ! $type->is_official;
                                    })
                                    ->native(false)
                                    ->live()
                                    ->columnSpan(4),

                                Placeholder::make('publication_logo_information')
                                    ->label('Logo institucional')
                                    ->content(function (Get $get): string {
                                        $type = TournamentType::find($get('tournament_type_id'));

                                        if (! $type?->is_official) {
                                            $club = Club::with('city.state.federation')->find($get('venue_id'));
                                            $externalState = State::with('federation')->find($get('external_venue_state_id'));

                                            if ($get('venue_type') === 'external') {
                                                return 'Se utilizará el logo de '
                                                    .($externalState?->federation?->name ?? 'la federación correspondiente a la provincia de la sede').'.';
                                            }

                                            if ($get('non_official_logo_source') === 'venue_club') {
                                                return $club
                                                    ? 'Se utilizará el logo de '.$club->name.'.'
                                                    : 'Se utilizará el logo del club cuando se asigne la sede.';
                                            }

                                            return 'Se utilizará el logo de '
                                                .($club?->city?->state?->federation?->name ?? 'la federación correspondiente al club organizador').'.';
                                        }

                                        if ($type->publication_logo_source === 'national_federation') {
                                            return 'Se utilizará el logo de '
                                                .($type->publicationFederation?->name ?? 'la federación nacional configurada en el tipo de torneo').'.';
                                        }

                                        $club = Club::with('city.state.federation')->find($get('venue_id'));

                                        return 'Se utilizará el logo de '
                                            .($club?->city?->state?->federation?->name ?? 'la federación provincial correspondiente al club organizador').'.';
                                    })
                                    ->columnSpan(4),
                            ])
                            ->disabled(fn () => ! Auth::user()->canGloballyOrInAnyDiscipline('EditField')),

                        Tab::make('Modalidades')
                            ->schema([
                                Repeater::make('tournamentModalities')
                                    ->relationship('tournamentModalities')
                                    ->label('Modalidades habilitadas')
                                    ->helperText('Cada modalidad administra sus categorías, precios y horarios.')
                                    ->minItems(1)->defaultItems(1)->collapsible()->live()
                                    ->afterStateUpdated(function (?array $state, Set $set): void {
                                        $categories = collect($state ?? [])->flatMap(
                                            fn (array $item): array => $item['categories'] ?? []
                                        )->unique()->values()->all();
                                        $set('../categories', $categories);
                                        $set('../manual_route_checks', []);
                                    })->itemLabel(
                                        fn (array $state): ?string => DisciplineModality::find($state['discipline_modality_id'] ?? null)?->name
                                    )->schema([
                                        Select::make('discipline_modality_id')->label('Modalidad')
                                            ->options(fn (Get $get) => DisciplineModality::query()
                                                ->where('discipline_id', $get('../../discipline_id'))->where('is_active', true)
                                                ->orderBy('order')->pluck('name', 'id'))
                                            ->required()->distinct()->searchable()->preload(),
                                        CheckboxList::make('categories')->label('Categorías')
                                            ->options(fn (Get $get) => Category::query()
                                                ->where('discipline_id', $get('../../discipline_id'))->orderBy('order')->pluck('name', 'id'))
                                            ->columns(3)->required()->live()->columnSpanFull(),
                                        Repeater::make('categoryPrices')->relationship('categoryPrices')
                                            ->label('Precios por categoría')->defaultItems(0)->columns(2)->schema([
                                                Select::make('category_id')->label('Categoría')->options(fn (Get $get) => Category::whereIn('id', $get('../../categories') ?? [])->pluck('name', 'id'))->required(),
                                                TextInput::make('price')->label('Precio')->numeric()->required(),
                                            ])->columnSpanFull(),
                                        Repeater::make('slots')->relationship('slots')->label('Horarios y cupos')
                                            ->defaultItems(0)->columns(4)->schema([
                                                TextInput::make('name')->label('Nombre')->columnSpan(1),
                                                DateTimePicker::make('starts_at')->label('Inicio')->native(false)->columnSpan(2),
                                                TextInput::make('max_players')->label('Máx. inscripciones')->numeric()->columnSpan(1),
                                                Toggle::make('is_active')->label('Activo')->default(true),
                                            ])->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),

                    ])->disabled(fn () => ! Auth::user()->canGloballyOrInAnyDiscipline('EditField')),

            ]);
    }

    public static function getResource(): string
    {
        return TournamentResource::class;
    }
}
