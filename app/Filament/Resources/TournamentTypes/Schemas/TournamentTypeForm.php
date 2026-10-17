<?php

namespace App\Filament\Resources\TournamentTypes\Schemas;

use App\Models\Discipline;
use App\Models\Federation;
use App\Models\TournamentInstance;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class TournamentTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('discipline_id')
                    ->label('Disciplina')
                    ->relationship(
                        name: 'discipline',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query
                            ->where('active', true)
                            ->when(Auth::user(), fn ($query, $user) => $user->scopeDisciplineQuery($query, 'Create:TournamentType'))
                            ->orderBy('name')
                    )
                    ->searchable()
                    ->preload()
                    ->default(fn () => Auth::user()?->defaultDisciplineId('Create:TournamentType'))
                    ->live()
                    ->required(),

                TextInput::make('name')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(255),

                TextInput::make('code')
                    ->label('Código')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                Toggle::make('has_handicap')
                    ->label('Con hándicap')
                    ->helperText('Indica si este tipo de torneo utiliza hándicap.')
                    ->default(false),

                Toggle::make('is_official')
                    ->label('Es oficial')
                    ->live()
                    ->afterStateUpdated(function (?bool $state, Set $set): void {
                        $set('publication_logo_source', $state ? 'venue_federation' : 'none');
                        $set('publication_federation_id', null);
                    })
                    ->required(),

                Select::make('publication_logo_source')
                    ->label('Logo para la publicación')
                    ->options([
                        'venue_federation' => 'Federación provincial del club organizador',
                        'national_federation' => 'Federación nacional seleccionada',
                    ])
                    ->helperText('El logo se incorpora automáticamente junto al flyer del torneo.')
                    ->visible(fn (Get $get): bool => (bool) $get('is_official'))
                    ->required(fn (Get $get): bool => (bool) $get('is_official'))
                    ->live()
                    ->native(false),

                Select::make('publication_federation_id')
                    ->label('Federación nacional')
                    ->options(fn (): array => Federation::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->visible(fn (Get $get): bool => (bool) $get('is_official')
                        && $get('publication_logo_source') === 'national_federation')
                    ->required(fn (Get $get): bool => (bool) $get('is_official')
                        && $get('publication_logo_source') === 'national_federation'),

                Toggle::make('exclusive_during_dates')
                    ->label('Exclusivo durante sus fechas')
                    ->helperText('Impide cualquier otro torneo de la misma disciplina durante fechas superpuestas.')
                    ->default(false)
                    ->required(),

                Toggle::make('assigns_points')
                    ->label('Asigna puntos')
                    ->live()
                    ->required(),

                Toggle::make('affects_ranking')
                    ->label('Afecta al ranking')
                    ->helperText(
                        'Los tipos habilitados participan del ranking propio de su disciplina y utilizan posiciones oficiales.'
                    )
                    ->live()
                    ->required(),

                Toggle::make('is_active')
                    ->label('Está activo')
                    ->required(),

                Select::make('scoring_method')
                    ->label('Método de puntuación')
                    ->options([
                        'position' => 'Posición o instancia alcanzada',
                    ])
                    ->visible(fn (Get $get): bool => (bool) $get('assigns_points'))
                    ->required(fn (Get $get): bool => (bool) $get('assigns_points'))
                    ->default('position')
                    ->live(),

                Repeater::make('scoring_rules')
                    ->label('Tabla de puntuación')
                    ->helperText(
                        'Cada tipo define las posiciones o instancias que utiliza y los puntos correspondientes.'
                    )
                    ->hintAction(
                        Action::make('importCurrentRankingPositions')
                            ->label('Importar posiciones actuales')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('gray')
                            ->requiresConfirmation()
                            ->modalHeading('Importar posiciones actuales')
                            ->modalDescription(
                                'Se reemplazará la tabla mostrada por todas las posiciones existentes del Ranking General.'
                            )
                            ->action(function (Repeater $component): void {
                                $items = [];

                                $instances = TournamentInstance::query()
                                    ->orderByDesc('instance')
                                    ->orderBy('id')
                                    ->get();

                                foreach ($instances as $instance) {
                                    $item = [
                                        'tournament_instance_id' => $instance->id,
                                        'code' => (string) $instance->code,
                                        'description' => $instance->description,
                                        'instance_value' => $instance->instance,
                                        'points' => $instance->points,
                                    ];

                                    $uuid = $component->generateUuid();

                                    if ($uuid) {
                                        $items[$uuid] = $item;
                                    } else {
                                        $items[] = $item;
                                    }
                                }

                                $component->rawState($items);
                            })
                    )
                    ->schema([
                        Select::make('tournament_instance_id')
                            ->label('Posición oficial')
                            ->options(function (): array {
                                return TournamentInstance::query()
                                    ->orderByDesc('instance')
                                    ->orderBy('id')
                                    ->get()
                                    ->mapWithKeys(
                                        fn (TournamentInstance $instance): array => [
                                            $instance->id => $instance->description,
                                        ]
                                    )
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->visible(
                                fn (Get $get): bool => (bool) $get('../../affects_ranking')
                                    && self::isFiveQuillas($get('../../discipline_id'))
                            )
                            ->required(
                                fn (Get $get): bool => (bool) $get('../../affects_ranking')
                                    && self::isFiveQuillas($get('../../discipline_id'))
                            )
                            ->afterStateUpdated(function (
                                $state,
                                callable $set
                            ): void {
                                if (! $state) {
                                    $set('code', null);
                                    $set('description', null);
                                    $set('instance_value', null);
                                    $set('points', null);

                                    return;
                                }

                                $instance = TournamentInstance::find($state);

                                $set('code', (string) $instance?->code);
                                $set('description', $instance?->description);
                                $set('instance_value', $instance?->instance);
                                $set('points', $instance?->points);
                            })
                            ->columnSpan(3),

                        TextInput::make('code')
                            ->label('Código')
                            ->helperText('Debe ser único dentro de este tipo.')
                            ->required()
                            ->maxLength(50)
                            ->distinct()
                            ->disabled(
                                fn (Get $get): bool => (bool) $get('../../affects_ranking')
                                    && self::isFiveQuillas($get('../../discipline_id'))
                            )
                            ->dehydrated()
                            ->columnSpan(2),

                        TextInput::make('description')
                            ->label('Descripción')
                            ->placeholder('Ej.: Semifinal')
                            ->required()
                            ->maxLength(255)
                            ->disabled(
                                fn (Get $get): bool => (bool) $get('../../affects_ranking')
                                    && self::isFiveQuillas($get('../../discipline_id'))
                            )
                            ->dehydrated()
                            ->columnSpan(3),

                        TextInput::make('instance_value')
                            ->label('Valor de orden')
                            ->helperText(
                                'Un valor mayor representa un mejor resultado.'
                            )
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->disabled(
                                fn (Get $get): bool => (bool) $get('../../affects_ranking')
                                    && self::isFiveQuillas($get('../../discipline_id'))
                            )
                            ->dehydrated()
                            ->columnSpan(2),

                        TextInput::make('points')
                            ->label('Puntos')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->required()
                            ->columnSpan(2),
                    ])
                    ->columns(12)
                    ->defaultItems(0)
                    ->addActionLabel('Agregar resultado')
                    ->reorderable()
                    ->collapsible()
                    ->visible(
                        fn (Get $get): bool => (bool) $get('assigns_points')
                            && $get('scoring_method') === 'position'
                    )
                    ->required(
                        fn (Get $get): bool => (bool) $get('assigns_points')
                            && $get('scoring_method') === 'position'
                    )
                    ->columnSpanFull(),
            ]);
    }

    private static function isFiveQuillas(mixed $disciplineId): bool
    {
        return filled($disciplineId) && Discipline::query()
            ->whereKey($disciplineId)
            ->where('code', 'five_quillas')
            ->exists();
    }
}
