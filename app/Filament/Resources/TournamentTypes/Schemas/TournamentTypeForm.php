<?php

namespace App\Filament\Resources\TournamentTypes\Schemas;

use App\Models\Discipline;
use App\Models\TournamentInstance;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

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
                        modifyQueryUsing: fn($query) => $query
                            ->where('active', true)
                            ->orderBy('name')
                    )
                    ->searchable()
                    ->preload()
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

                Select::make('participation_mode')
                    ->label('Modalidad de participación')
                    ->options([
                        'individual' => 'Individual',
                        'pairs' => 'Parejas',
                    ])
                    ->default('individual')
                    ->native(false)
                    ->required(),

                Toggle::make('has_handicap')
                    ->label('Con hándicap')
                    ->helperText('Indica si este tipo de torneo utiliza hándicap.')
                    ->default(false),

                Toggle::make('is_official')
                    ->label('Es oficial')
                    ->required(),

                Toggle::make('assigns_points')
                    ->label('Asigna puntos')
                    ->live()
                    ->required(),

                Toggle::make('affects_ranking')
                    ->label('Afecta al ranking')
                    ->helperText(
                        'Los tipos habilitados participan del Ranking General y utilizan las posiciones oficiales.'
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
                    ->visible(function (Get $get): bool {
                        $disciplineId = $get('discipline_id');

                        if (! $disciplineId) {
                            return false;
                        }

                        return Discipline::query()
                            ->whereKey($disciplineId)
                            ->where('code', 'five_quillas')
                            ->exists();
                    })
                    ->required(function (Get $get): bool {
                        if (! $get('assigns_points')) {
                            return false;
                        }

                        $disciplineId = $get('discipline_id');

                        if (! $disciplineId) {
                            return false;
                        }

                        return Discipline::query()
                            ->whereKey($disciplineId)
                            ->where('code', 'five_quillas')
                            ->exists();
                    })
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
                                        fn(TournamentInstance $instance): array => [
                                            $instance->id =>
                                            $instance->description,
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
                                fn(Get $get): bool =>
                                (bool) $get('../../affects_ranking')
                            )
                            ->required(
                                fn(Get $get): bool =>
                                (bool) $get('../../affects_ranking')
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
                                fn(Get $get): bool =>
                                (bool) $get('../../affects_ranking')
                            )
                            ->dehydrated()
                            ->columnSpan(2),

                        TextInput::make('description')
                            ->label('Descripción')
                            ->placeholder('Ej.: Semifinal')
                            ->required()
                            ->maxLength(255)
                            ->disabled(
                                fn(Get $get): bool =>
                                (bool) $get('../../affects_ranking')
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
                                fn(Get $get): bool =>
                                (bool) $get('../../affects_ranking')
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
                        fn(Get $get): bool =>
                        (bool) $get('assigns_points')
                            && $get('scoring_method') === 'position'
                    )
                    ->required(
                        fn(Get $get): bool =>
                        (bool) $get('assigns_points')
                            && $get('scoring_method') === 'position'
                    )
                    ->columnSpanFull(),
            ]);
    }
}
