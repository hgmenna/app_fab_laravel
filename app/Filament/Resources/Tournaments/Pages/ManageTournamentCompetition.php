<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasMatch;
use App\Models\Category;
use App\Services\FiveQuillasCompetitionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ManageTournamentCompetition extends Page
{
    use InteractsWithRecord;

    protected static string $resource = TournamentResource::class;
    protected string $view = 'filament.resources.tournaments.pages.manage-tournament-competition';
    protected static ?string $navigationLabel = 'Competencia';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->loadMissing(['discipline', 'type', 'slots']);
        abort_unless($this->record->discipline?->code === 'five_quillas', 404);
    }

    public function getTitle(): string
    {
        return 'Organización · '.$this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('initialize')
                ->label(fn (): string => $this->competition() ? 'Actualizar inscriptos' : 'Iniciar organización')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function (): void {
                    $competition = app(FiveQuillasCompetitionService::class)->initialize($this->record);
                    Notification::make()->success()->title('Competencia preparada')
                        ->body($competition->entries()->where('status', '!=', 'withdrawn')->count().' jugadores aprobados disponibles por horario.')
                        ->send();
                }),
            Action::make('publish')
                ->label(fn (): string => $this->competition()?->is_public ? 'Ocultar resultados públicos' : 'Mostrar resultados al público')
                ->icon(fn (): string => $this->competition()?->is_public ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                ->color(fn (): string => $this->competition()?->is_public ? 'gray' : 'success')
                ->visible(fn (): bool => $this->competition() !== null)
                ->requiresConfirmation()
                ->action(function (): void {
                    $competition = $this->competition();
                    $publish = ! $competition->is_public;
                    $competition->update(['is_public' => $publish, 'status' => $publish ? 'published' : 'draft']);
                    $competition->phases()->update(['is_public' => false]);
                    $competition->phases()->where('is_enabled', true)->update(['is_public' => $publish]);
                    $competition->phases()->with('groups')->get()->flatMap->groups->each(function ($group) use ($publish): void {
                        $phaseEnabled = (bool) $group->phase()->value('is_enabled');
                        $group->update(['is_public' => $publish && $phaseEnabled]);
                        $group->matches()->update(['is_public' => $publish && $phaseEnabled]);
                    });
                    Notification::make()->success()->title($publish ? 'Los resultados ya están visibles para el público' : 'Los resultados dejaron de estar visibles para el público')->send();
                }),
            Action::make('configureFormat')
                ->label('Configurar formato')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('info')
                ->visible(fn (): bool => $this->competition() !== null)
                ->fillForm(fn (): array => [
                    'phases' => $this->competition()->phases->map(fn ($phase): array => [
                        'id' => $phase->id,
                        'name' => $phase->name,
                        'phase_type' => $phase->phase_type,
                        'sets_to_play' => $phase->sets_to_play,
                        'points_per_set' => $phase->points_per_set,
                        'seeding_method' => $phase->phase_type === 'zones'
                            && ! in_array($phase->seeding_method, ['manual', 'national_ranking'], true)
                                ? 'manual'
                                : $phase->seeding_method,
                        'split_by_slot' => $phase->split_by_slot,
                        'is_enabled' => $phase->is_enabled ?? true,
                        'qualifiers' => data_get($phase->settings, 'qualifiers'),
                        'entry_category_ids' => data_get($phase->settings, 'entry_category_ids', []),
                        'tables' => data_get($phase->settings, 'tables'),
                        'estimated_minutes' => data_get($phase->settings, 'estimated_minutes'),
                        'rest_minutes' => data_get($phase->settings, 'rest_minutes', 0),
                        'bracket_size' => data_get($phase->settings, 'bracket_size')
                            ?: match ($phase->code) {
                                'national' => 64,
                                'master-final' => 32,
                                'final' => 16,
                                default => null,
                            },
                        'schedules' => $phase->phase_type === 'zones'
                            ? []
                            : (data_get($phase->settings, 'schedules') ?: [[
                                'name' => 'Horario principal',
                                'starts_at' => null,
                                'round_number' => 1,
                                'bracket_section' => null,
                                'from_match' => null,
                                'to_match' => null,
                                'tables' => data_get($phase->settings, 'tables'),
                            ]]),
                    ])->values()->all(),
                ])
                ->schema([
                    Repeater::make('phases')
                        ->label('Fases de la competencia')
                        ->schema([
                            Hidden::make('id'),
                            TextInput::make('name')->label('Nombre')->required()->maxLength(255)->columnSpan(2),
                            Select::make('phase_type')->label('Formato')->options([
                                'zones' => 'Zonas',
                                'slot_elimination' => 'Eliminación por horario',
                                'play_in' => 'Emparejamiento / reclasificación',
                                'bracket' => 'Cuadro eliminatorio',
                            ])->required()->native(false)->live()
                                ->afterStateUpdated(fn (Set $set) => $set('seeding_method', 'manual'))
                                ->columnSpan(2),
                            TextInput::make('sets_to_play')->label('Sets')->integer()->minValue(1)->default(3)->required(),
                            TextInput::make('points_per_set')->label('Puntos por set')->integer()->minValue(1)->default(60)->required(),
                            Select::make('seeding_method')->label('Ubicación de jugadores')
                                ->options(fn (Get $get): array => match ($get('phase_type')) {
                                    'zones' => [
                                        'manual' => 'Carga manual',
                                        'national_ranking' => 'Ranking nacional',
                                    ],
                                    'slot_elimination' => [
                                        'manual' => 'Carga manual',
                                        'previous_results' => 'Resultados de la fase anterior',
                                        'automatic_draw' => 'Sorteo automático',
                                    ],
                                    'play_in' => [
                                        'manual' => 'Carga manual',
                                        'national_ranking' => 'Ranking nacional',
                                        'previous_results' => 'Resultados de la fase anterior',
                                    ],
                                    default => [
                                        'manual' => 'Carga manual',
                                        'previous_results' => 'Resultados de la fase anterior',
                                        'national_ranking' => 'Ranking nacional',
                                        'number_draw' => 'Sorteo presencial de posición en la grilla',
                                    ],
                                })
                                ->default('manual')->required()->native(false)->columnSpan(2)
                                ->helperText(fn (Get $get): ?string => $get('phase_type') === 'bracket'
                                    ? 'El sorteo presencial se usa cuando cada jugador extrae el número de su posición en el cuadro.'
                                    : null),
                            Select::make('entry_category_ids')->label('Categorías que ingresan en esta fase')
                                ->options(fn (): array => Category::query()
                                    ->where('discipline_id', $this->record->discipline_id)
                                    ->orderBy('order')->orderBy('name')->pluck('name', 'id')->all())
                                ->multiple()->searchable()->preload()->native(false)->columnSpan(2)
                                ->helperText('Los inscriptos de estas categorías se incorporan directamente aquí. Las categorías no asignadas ingresan en la primera fase.'),
                            TextInput::make('qualifiers')->label('Clasificados a la siguiente fase')->integer()->minValue(1)
                                ->helperText('En la última fase se ignora porque allí finaliza el torneo.'),
                            TextInput::make('tables')->label('Mesas disponibles')->integer()->minValue(1),
                            TextInput::make('estimated_minutes')->label('Duración por partido')->suffix('min')->integer()->minValue(1),
                            TextInput::make('rest_minutes')->label('Descanso entre partidos')->suffix('min')->integer()->minValue(0)->default(0),
                            Select::make('bracket_size')->label('Tamaño del cuadro')->options([
                                16 => '16 posiciones',
                                32 => '32 posiciones',
                                64 => '64 posiciones',
                            ])->visible(fn (Get $get): bool => $get('phase_type') === 'bracket')
                                ->required(fn (Get $get): bool => $get('phase_type') === 'bracket')
                                ->native(false),
                            Toggle::make('split_by_slot')->label('Usar los horarios definidos en el torneo')->inline(false)
                                ->helperText('Cada horario conserva sus propios inscriptos, zonas y partidos.'),
                            Toggle::make('is_enabled')->label('Fase habilitada')->default(true)->inline(false),
                            Repeater::make('schedules')
                                ->label('Horarios propios de esta fase')
                                ->helperText('Usalo en fases posteriores a las zonas. Primera Nacional puede dividirse en dos horarios independientes.')
                                ->table([
                                    TableColumn::make('Nombre')->markAsRequired(),
                                    TableColumn::make('Fecha y hora de inicio'),
                                    TableColumn::make('Ronda'),
                                    TableColumn::make('Sector de la grilla'),
                                    TableColumn::make('Desde partido'),
                                    TableColumn::make('Hasta partido'),
                                    TableColumn::make('Mesas'),
                                ])
                                ->schema([
                                    TextInput::make('name')->hiddenLabel()->required()->maxLength(100),
                                    DateTimePicker::make('starts_at')->hiddenLabel()->seconds(false),
                                    TextInput::make('round_number')->hiddenLabel()->integer()->minValue(1)->default(1)->required(),
                                    TextInput::make('bracket_section')->hiddenLabel()->placeholder('Ej. 1-64 a 29-4')->maxLength(100),
                                    TextInput::make('from_match')->hiddenLabel()->integer()->minValue(1)->placeholder('Desde'),
                                    TextInput::make('to_match')->hiddenLabel()->integer()->minValue(1)->placeholder('Hasta'),
                                    TextInput::make('tables')->hiddenLabel()->integer()->minValue(1),
                                ])
                                ->visible(fn (Get $get): bool => $get('phase_type') !== 'zones')
                                ->defaultItems(1)
                                ->addActionLabel('Agregar horario de fase')
                                ->reorderable()
                                ->columnSpanFull(),
                        ])
                        ->columns(6)
                        ->defaultItems(1)
                        ->minItems(1)
                        ->addActionLabel('Agregar fase')
                        ->reorderable()
                        ->collapsible()
                        ->cloneable(false)
                        ->columnSpanFull(),
                ])
                ->modalHeading('Formato de competencia del torneo')
                ->modalDescription('El orden de las filas determina el orden de disputa. Las zonas usan los horarios ya creados en el torneo; no comparten jugadores ni horarios entre sí. Las fases con resultados cargados se conservan para preservar el historial.')
                ->modalWidth('7xl')
                ->action(function (array $data): void {
                    $enabled = app(FiveQuillasCompetitionService::class)->configureFormat(
                        $this->competition(),
                        $data['phases'] ?? [],
                    );
                    Notification::make()->success()->title('Formato actualizado')
                        ->body($enabled.' fases habilitadas para este torneo.')->send();
                }),
            Action::make('applyCompetitionTemplate')
                ->label('Elegir formato inicial')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(fn (): bool => $this->competition() !== null)
                ->schema([
                    Select::make('format')->label('Formato para este torneo')->options([
                        'zones_final' => 'Zonas y cuadro final',
                        'amistad' => 'Zonas, fase intermedia y cuadro final',
                        'direct_elimination' => 'Eliminación directa',
                        'circuit_argentino' => 'Circuito Argentino',
                    ])->default('zones_final')->required()->native(false),
                ])
                ->requiresConfirmation()
                ->modalHeading('Elegir formato inicial del torneo')
                ->modalDescription('El formato se aplica solamente a este torneo. Luego podés modificar libremente sus fases y nombres. Solo se permite antes de cargar zonas o partidos.')
                ->action(function (array $data): void {
                    $count = app(FiveQuillasCompetitionService::class)->applyCompetitionTemplate(
                        $this->competition(),
                        (string) $data['format'],
                    );
                    Notification::make()->success()->title('Plantilla aplicada')
                        ->body($count.' fases creadas para este torneo.')->send();
                }),
        ];
    }

    public function competition(): ?FiveQuillasCompetition
    {
        return FiveQuillasCompetition::query()
            ->where('tournament_id', $this->record->id)
            ->with(['phases.groups.slot', 'phases.groups.groupEntries.entry.player.club', 'phases.groups.matches.playerOne.player', 'phases.groups.matches.playerTwo.player', 'phases.matches.playerOne.player', 'phases.matches.playerTwo.player', 'entries.player.club', 'entries.player.category', 'entries.sourceSlot', 'entries.entryPhase'])
            ->first();
    }

    public function entryOptionsForSlot(int $slotId): array
    {
        if ($slotId < 1 || ! $this->competition()) {
            return [];
        }

        return $this->competition()->entries()
            ->where('source_slot_id', $slotId)
            ->whereIn('status', ['eligible', 'assigned'])
            ->with(['player.club'])
            ->get()
            ->sortBy(fn ($entry): string => $entry->player?->full_name ?? '')
            ->mapWithKeys(fn ($entry): array => [
                $entry->id => ($entry->player?->full_name ?? 'Jugador').' — '.($entry->player?->club?->name ?? 'INDEPENDIENTE'),
            ])->all();
    }

    public function zoneRowsForSlot(int $slotId): array
    {
        if ($slotId < 1 || ! $this->competition()) {
            return [['name' => 'Zona 1']];
        }

        $phase = $this->competition()->phases
            ->first(fn ($phase): bool => $phase->phase_type === 'zones' && $phase->is_enabled);

        if (! $phase) {
            return [['name' => 'Zona 1']];
        }

        $rows = $phase->groups()
            ->where('tournament_slot_id', $slotId)
            ->with('groupEntries')
            ->orderBy('sort_order')
            ->get()
            ->map(function ($group): array {
                $entries = $group->groupEntries->keyBy('position');

                return [
                    'name' => $group->name,
                    ...collect(range(1, 4))->mapWithKeys(fn (int $position): array => [
                        'player_'.$position => $entries->get($position)?->entry_id,
                    ])->all(),
                ];
            })
            ->values()
            ->all();

        return $rows ?: [['name' => 'Zona 1']];
    }

    public function createZonesAction(): Action
    {
        return Action::make('createZones')
            ->label(fn (array $arguments): string => filled($arguments['slotId'] ?? null)
                ? 'Configurar zonas'
                : 'Cargar zonas')
            ->icon('heroicon-o-squares-2x2')->color('warning')->size('sm')
            ->fillForm(function (array $arguments): array {
                $slotId = (int) ($arguments['slotId'] ?? 0);

                return [
                    'slot_id' => $slotId ?: null,
                    'zones' => $this->zoneRowsForSlot($slotId),
                ];
            })
            ->schema([
                Select::make('slot_id')->label('Horario')
                    ->options(fn (): array => $this->record->slots()->where('is_active', true)->orderBy('starts_at')->pluck('name', 'id')->all())
                    ->required()->searchable()->live()
                    ->afterStateUpdated(fn ($state, Set $set) => $set('zones', $this->zoneRowsForSlot((int) $state))),
                Repeater::make('zones')->label('Zonas del horario')
                    ->table([
                        TableColumn::make('Zona'), TableColumn::make('Posición 1'),
                        TableColumn::make('Posición 2'), TableColumn::make('Posición 3'),
                        TableColumn::make('Posición 4'),
                    ])
                    ->schema([
                        TextInput::make('name')->placeholder('Zona 1')->maxLength(255),
                        ...collect(range(1, 4))->map(fn (int $position) => Select::make('player_'.$position)
                            ->hiddenLabel()->options(fn (Get $get): array => $this->entryOptionsForSlot((int) $get('../../slot_id')))
                            ->searchable()->preload()->native(false)->placeholder('LIBRE'))->all(),
                    ])
                    ->defaultItems(1)->minItems(1)->addActionLabel('Agregar zona')
                    ->reorderable(false)->columnSpanFull(),
            ])
            ->modalHeading('Carga manual de zonas')
            ->modalDescription('Elegí un horario y ubicá sus jugadores en zonas de tres o cuatro integrantes. Si ya existen zonas para ese horario, se cargan para poder corregirlas.')
            ->modalWidth('7xl')->requiresConfirmation()
            ->action(function (array $data, array $arguments): void {
                $this->competition()->phases()->whereKey((int) $arguments['phaseId'])
                    ->where('phase_type', 'zones')->where('is_enabled', true)->firstOrFail();
                $count = app(FiveQuillasCompetitionService::class)->createManualZones(
                    $this->competition(), (int) $data['slot_id'], $data['zones'] ?? [],
                );
                Notification::make()->success()->title('Zonas guardadas')
                    ->body($count.' zonas quedaron preparadas.')->send();
            });
    }

    public function preparePhaseAction(): Action
    {
        return Action::make('preparePhase')
            ->label('Preparar fase')->icon('heroicon-o-forward')->color('warning')->size('sm')
            ->requiresConfirmation()
            ->modalDescription('Incorpora los clasificados de la fase anterior, agrega quienes ingresan directamente aquí y crea los cruces.')
            ->action(function (array $arguments): void {
                $phase = $this->competition()->phases()->whereKey((int) $arguments['phaseId'])
                    ->where('phase_type', '!=', 'zones')->where('is_enabled', true)->firstOrFail();
                $count = app(FiveQuillasCompetitionService::class)->preparePhase($phase);
                Notification::make()->success()->title('Fase preparada')
                    ->body($count > 0 ? $count.' partidos quedaron creados.' : 'La fase no necesitó partidos adicionales.')->send();
            });
    }

    public function schedulePhaseAction(): Action
    {
        return Action::make('schedulePhase')
            ->label('Asignar horarios')->icon('heroicon-o-clock')->color('primary')->size('sm')
            ->requiresConfirmation()
            ->modalDescription('Asigna la hora configurada a los partidos de este bloque. La mesa se elige después, al llamar cada partido.')
            ->action(function (array $arguments): void {
                $phase = $this->competition()->phases()->whereKey((int) $arguments['phaseId'])
                    ->where('phase_type', '!=', 'zones')->where('is_enabled', true)->firstOrFail();
                $count = app(FiveQuillasCompetitionService::class)->scheduleConfiguredMatches(
                    $phase, (int) $arguments['scheduleIndex'],
                );
                Notification::make()->success()->title('Horarios asignados')
                    ->body($count.' partidos recibieron su horario.')->send();
            });
    }

    public function recordResultAction(): Action
    {
        return Action::make('recordResult')
            ->label(function (array $arguments): string {
                $match = $this->competitionMatch((int) ($arguments['matchId'] ?? 0));

                return $match?->status === 'completed' ? 'Editar resultado' : 'Resultado';
            })
            ->icon('heroicon-o-pencil-square')->color('success')->size('sm')
            ->fillForm(function (array $arguments): array {
                $match = $this->competitionMatch((int) ($arguments['matchId'] ?? 0));
                $sets = collect(data_get($match?->set_scores, 'sets', []))
                    ->map(fn (array $set): array => [
                        'player_one_points' => (int) ($set['player_one_points'] ?? 0),
                        'player_two_points' => (int) ($set['player_two_points'] ?? 0),
                    ])->values()->all();

                return ['scores' => $sets ?: [
                    ['player_one_points' => null, 'player_two_points' => null],
                    ['player_one_points' => null, 'player_two_points' => null],
                ]];
            })
            ->schema([
                Placeholder::make('players')
                    ->label('Partido')
                    ->content(fn (array $arguments): string => $this->matchPlayersLabel((int) ($arguments['matchId'] ?? 0)))
                    ->columnSpanFull(),
                Repeater::make('scores')->label('Resultado de los sets')
                    ->table([
                        TableColumn::make('Puntos jugador 1')->markAsRequired(),
                        TableColumn::make('Puntos jugador 2')->markAsRequired(),
                    ])
                    ->schema([
                        TextInput::make('player_one_points')->hiddenLabel()->integer()->minValue(0)->required(),
                        TextInput::make('player_two_points')->hiddenLabel()->integer()->minValue(0)->required(),
                    ])
                    ->defaultItems(2)->minItems(2)->maxItems(5)
                    ->addActionLabel('Agregar set')->reorderable(false)->columnSpanFull(),
            ])
            ->modalHeading(fn (array $arguments): string => 'Resultado · '.$this->matchPlayersLabel((int) ($arguments['matchId'] ?? 0)))
            ->modalDescription('Cargá solamente los sets disputados. El sistema determina el ganador y actualiza la clasificación.')
            ->action(function (array $data, array $arguments): void {
                $match = FiveQuillasMatch::query()
                    ->whereHas('phase', fn ($query) => $query->where('competition_id', $this->competition()->id))
                    ->whereNotNull('player_one_entry_id')->whereNotNull('player_two_entry_id')->where('is_bye', false)
                    ->findOrFail((int) $arguments['matchId']);
                app(FiveQuillasCompetitionService::class)->recordMatchResult($match, $data['scores'] ?? []);
                Notification::make()->success()->title('Resultado guardado')
                    ->body('La clasificación y la progresión del cuadro fueron actualizadas.')->send();
            });
    }

    private function matchPlayersLabel(int $matchId): string
    {
        $match = $this->competitionMatch($matchId);

        return ($match?->playerOne?->player?->full_name ?? 'A definir')
            .' vs '
            .($match?->playerTwo?->player?->full_name ?? 'A definir');
    }

    private function competitionMatch(int $matchId): ?FiveQuillasMatch
    {
        if ($matchId < 1 || ! $this->competition()) {
            return null;
        }

        return FiveQuillasMatch::query()
            ->whereHas('phase', fn ($query) => $query->where('competition_id', $this->competition()->id))
            ->with(['playerOne.player', 'playerTwo.player'])
            ->find($matchId);
    }

    public function callMatchAction(): Action
    {
        return Action::make('callMatch')
            ->label('Llamar')
            ->icon('heroicon-o-megaphone')
            ->color('info')
            ->size('sm')
            ->schema([
                TextInput::make('table_number')->label('Mesa disponible')->integer()->minValue(1)->required(),
            ])
            ->modalHeading('Llamar partido')
            ->modalDescription('Asigná la mesa disponible al momento de llamar a los jugadores.')
            ->action(function (array $data, array $arguments): void {
                $match = FiveQuillasMatch::query()
                    ->whereHas('phase', fn ($query) => $query->where('competition_id', $this->competition()->id))
                    ->whereNotNull('player_one_entry_id')->whereNotNull('player_two_entry_id')
                    ->where('is_bye', false)->where('status', '!=', 'completed')
                    ->findOrFail((int) $arguments['matchId']);
                $match->update(['table_number' => (int) $data['table_number'], 'status' => 'called']);
                Notification::make()->success()->title('Partido llamado')
                    ->body('La mesa '.$data['table_number'].' quedó asignada.')->send();
            });
    }

    protected function getViewData(): array
    {
        $competition = $this->competition();
        $entries = $competition?->entries->where('status', '!=', 'withdrawn') ?? collect();

        return [
            'competition' => $competition,
            'entriesBySlot' => $entries->groupBy(fn ($entry): string => $entry->sourceSlot?->name ?? 'SIN HORARIO ASIGNADO'),
            'entriesByPhase' => $entries->groupBy(fn ($entry): string => $entry->entryPhase?->name ?? 'PRIMERA FASE'),
            'publicJsonUrl' => $competition?->is_public
                ? route('five-quillas-competition.public', ['slug' => $competition->public_slug])
                : null,
            'publicPageUrl' => $competition?->is_public
                ? route('five-quillas-competition.page', ['slug' => $competition->public_slug])
                : null,
        ];
    }
}
