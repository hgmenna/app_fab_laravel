<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasMatch;
use App\Models\FiveQuillasEntry;
use App\Models\Category;
use App\Services\FiveQuillasCompetitionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class ManageTournamentCompetition extends Page
{
    use InteractsWithRecord;

    protected static string $resource = TournamentResource::class;
    protected string $view = 'filament.resources.tournaments.pages.manage-tournament-competition';
    protected static ?string $navigationLabel = 'Competencia';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->loadMissing([
            'discipline', 'type.publicationFederation', 'slots',
            'venue.city.state.federation', 'externalVenueState.federation',
            'tournamentModalities.modality',
        ]);
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
                ->label('Configurar vista pública')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->visible(fn (): bool => $this->competition() !== null)
                ->fillForm(function (): array {
                    $competition = $this->competition();
                    $competition->loadMissing(['phases.groups']);

                    return [
                        'is_public' => (bool) $competition->is_public,
                        'phase_ids' => $competition->phases->where('is_public', true)->pluck('id')->map(fn ($id): string => (string) $id)->all(),
                        'zone_slot_keys' => $competition->phases->where('phase_type', 'zones')->flatMap->groups
                            ->where('is_public', true)->map(fn ($group): string => $group->tournament_slot_id ? 'slot_'.$group->tournament_slot_id : 'without_slot')->unique()->values()->all(),
                        'show_final_standings' => (bool) data_get($competition->settings, 'public.show_final_standings', true),
                    ];
                })
                ->schema([
                    Toggle::make('is_public')
                        ->label('Mostrar el desarrollo del torneo al público')
                        ->live()
                        ->helperText('Al desactivarlo, el acceso público completo queda oculto.'),
                    CheckboxList::make('phase_ids')
                        ->label('Fases visibles')
                        ->options(fn (): array => $this->competition()->phases()
                            ->where('is_enabled', true)->orderBy('sort_order')->pluck('name', 'id')->all())
                        ->columns(2)
                        ->visible(fn (Get $get): bool => (bool) $get('is_public')),
                    CheckboxList::make('zone_slot_keys')
                        ->label('Horarios de zonas visibles')
                        ->options(function (): array {
                            $groups = $this->competition()->phases()->where('phase_type', 'zones')
                                ->with('groups.slot')->get()->flatMap->groups;

                            return $groups->groupBy(fn ($group): string => $group->tournament_slot_id ? 'slot_'.$group->tournament_slot_id : 'without_slot')
                                ->map(fn ($slotGroups, $key): string => $key === 'without_slot'
                                    ? 'Sin horario asignado'
                                    : ($slotGroups->first()?->slot?->name ?? 'Horario'))
                                ->all();
                        })
                        ->columns(2)
                        ->visible(fn (Get $get): bool => (bool) $get('is_public'))
                        ->helperText('Podés publicar un horario de zonas sin mostrar los demás.'),
                    Toggle::make('show_final_standings')
                        ->label('Mostrar la pestaña Posiciones finales')
                        ->default(true)
                        ->visible(fn (Get $get): bool => (bool) $get('is_public')),
                ])
                ->modalHeading('Vista pública del torneo')
                ->modalDescription('Elegí exactamente qué fases y horarios estarán disponibles para los usuarios.')
                ->modalWidth('3xl')
                ->action(function (array $data): void {
                    $competition = $this->competition();
                    $competition->loadMissing(['phases.groups']);
                    $publish = (bool) ($data['is_public'] ?? false);
                    $phaseIds = collect($data['phase_ids'] ?? [])->map(fn ($id): int => (int) $id);
                    $slotKeys = collect($data['zone_slot_keys'] ?? [])->map(fn ($key): string => (string) $key);
                    $settings = $competition->settings ?? [];
                    data_set($settings, 'public.show_final_standings', (bool) ($data['show_final_standings'] ?? false));

                    $competition->update([
                        'is_public' => $publish,
                        'status' => $publish && $competition->status === 'draft' ? 'published' : $competition->status,
                        'settings' => $settings,
                    ]);

                    $competition->phases->each(function ($phase) use ($publish, $phaseIds, $slotKeys): void {
                        $phasePublic = $publish && $phase->is_enabled && $phaseIds->contains((int) $phase->id);
                        $phase->update(['is_public' => $phasePublic]);
                        $phase->matches()->whereNull('group_id')->update(['is_public' => $phasePublic]);
                        $phase->groups->each(function ($group) use ($phase, $phasePublic, $slotKeys): void {
                            $slotKey = $group->tournament_slot_id ? 'slot_'.$group->tournament_slot_id : 'without_slot';
                            $groupPublic = $phasePublic && ($phase->phase_type !== 'zones' || $slotKeys->contains($slotKey));
                            $group->update(['is_public' => $groupPublic]);
                            $group->matches()->update(['is_public' => $groupPublic]);
                        });
                    });

                    Notification::make()->success()
                        ->title($publish ? 'Vista pública actualizada' : 'El desarrollo quedó oculto al público')
                        ->send();
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
                        'previous_phase_id' => data_get($phase->settings, 'previous_phase_id'),
                        'next_phase_id' => data_get($phase->settings, 'next_phase_id'),
                        'progressive_fill' => (bool) data_get($phase->settings, 'progressive_fill', false),
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
                        'direct_entry_seed_count' => data_get($phase->settings, 'direct_entry_seed_count'),
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
                            TextInput::make('sets_to_play')
                                ->label(fn (Get $get): string => $get('phase_type') === 'play_in'
                                    ? 'Sets de los emparejamientos'
                                    : 'Sets')
                                ->integer()->minValue(1)->default(3)->required()
                                ->helperText(fn (Get $get): ?string => $get('phase_type') === 'play_in'
                                    ? 'Se aplica solamente a los partidos de reclasificación. Para Circuito Argentino usá 3 sets aquí y creá después el cuadro Nacional de 64 con 5 sets.'
                                    : null),
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
                                ->helperText(fn (Get $get): ?string => match ($get('phase_type')) {
                                    'bracket' => 'El sorteo presencial se usa cuando cada jugador extrae el número de su posición en el cuadro.',
                                    'play_in' => 'En Circuito Argentino elegí Ranking nacional: los clasificados se ordenan por ranking para determinar quiénes deben disputar la reclasificación.',
                                    default => null,
                                }),
                            Select::make('entry_category_ids')->label('Categorías que ingresan en esta fase')
                                ->options(fn (): array => Category::query()
                                    ->where('discipline_id', $this->record->discipline_id)
                                    ->orderBy('order')->orderBy('name')->pluck('name', 'id')->all())
                                ->multiple()->searchable()->preload()->native(false)->columnSpan(2)
                                ->helperText(fn (Get $get): string => $get('phase_type') === 'play_in'
                                    ? 'En Circuito Argentino, la categoría Primera Nacional debe ingresar en el cuadro Nacional de 64 posterior, no en esta reclasificación.'
                                    : 'Los inscriptos de estas categorías se incorporan directamente aquí. En el cuadro Nacional se ordenan por ranking antes de ocupar sus posiciones.'),
                            Select::make('previous_phase_id')->label('Fase anterior')
                                ->options(fn (): array => $this->competition()->phases()
                                    ->orderBy('sort_order')->pluck('name', 'id')->all())
                                ->native(false)->searchable()->placeholder('Primera fase')
                                ->helperText('Los clasificados de esta fase serán los participantes de la fase actual.'),
                            Select::make('next_phase_id')->label('Fase posterior')
                                ->options(fn (): array => $this->competition()->phases()
                                    ->orderBy('sort_order')->pluck('name', 'id')->all())
                                ->native(false)->searchable()->placeholder('Última fase')
                                ->helperText('Los clasificados de la fase actual pasarán a esta fase.'),
                            Toggle::make('progressive_fill')->label('Completar la grilla progresivamente')
                                ->visible(fn (Get $get): bool => $get('phase_type') === 'bracket')
                                ->helperText('Permite preparar el cuadro antes de finalizar la fase anterior e incorpora cada clasificado en su número de sorteo.')
                                ->inline(false),
                            TextInput::make('qualifiers')->label('Clasificados a la siguiente fase')->integer()->minValue(1)
                                ->helperText('En la última fase se ignora porque allí finaliza el torneo.'),
                            TextInput::make('tables')->label('Mesas disponibles')->integer()->minValue(1),
                            TextInput::make('estimated_minutes')->label('Duración por partido')->suffix('min')->integer()->minValue(1),
                            TextInput::make('rest_minutes')->label('Descanso entre partidos')->suffix('min')->integer()->minValue(0)->default(0),
                            Select::make('bracket_size')->label('Tamaño del cuadro')->options([
                                2 => '2 posiciones',
                                4 => '4 posiciones',
                                8 => '8 posiciones',
                                16 => '16 posiciones',
                                32 => '32 posiciones',
                                64 => '64 posiciones',
                                128 => '128 posiciones',
                                256 => '256 posiciones',
                            ])->visible(fn (Get $get): bool => $get('phase_type') === 'bracket')
                                ->required(fn (Get $get): bool => $get('phase_type') === 'bracket')
                                ->helperText('Al preparar la fase, el sistema ajusta el cuadro automáticamente a la cantidad real de jugadores.')
                                ->native(false),
                            TextInput::make('direct_entry_seed_count')
                                ->label('Posiciones reservadas para ingreso directo')
                                ->integer()->minValue(0)
                                ->visible(fn (Get $get): bool => $get('phase_type') === 'bracket')
                                ->helperText('Ejemplo Circuito Argentino: 32. La categoría que ingresa en esta fase ocupa las posiciones 1 a 32 por ranking; los clasificados de la fase anterior ocupan las restantes.'),
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
            ->with(['phases.groups.slot', 'phases.groups.groupEntries.entry.player.club', 'phases.groups.matches.playerOne.player', 'phases.groups.matches.playerTwo.player', 'phases.matches.playerOne.player', 'phases.matches.playerTwo.player', 'phases.matches.winner.player', 'entries.player.club', 'entries.player.category', 'entries.sourceSlot', 'entries.entryPhase', 'entries.registration'])
            ->first();
    }

    public function entryOptionsForSlot(int $slotId, array $excludedEntryIds = []): array
    {
        if ($slotId < 1 || ! $this->competition()) {
            return [];
        }

        return $this->competition()->entries()
            ->where('source_slot_id', $slotId)
            ->whereIn('status', ['eligible', 'assigned'])
            ->when($excludedEntryIds !== [], fn ($query) => $query->whereNotIn('id', $excludedEntryIds))
            ->with(['player.club', 'player.category'])
            ->get()
            ->sort(function ($left, $right): int {
                $leftRanking = (int) ($left->national_ranking_snapshot ?? 0);
                $rightRanking = (int) ($right->national_ranking_snapshot ?? 0);
                $leftHasRanking = $leftRanking > 0;
                $rightHasRanking = $rightRanking > 0;

                if ($leftHasRanking !== $rightHasRanking) {
                    return $leftHasRanking ? -1 : 1;
                }

                if ($leftHasRanking && $leftRanking !== $rightRanking) {
                    return $leftRanking <=> $rightRanking;
                }

                if (! $leftHasRanking) {
                    $categoryOrder = ((int) ($left->player?->category?->order ?? PHP_INT_MAX))
                        <=> ((int) ($right->player?->category?->order ?? PHP_INT_MAX));
                    if ($categoryOrder !== 0) {
                        return $categoryOrder;
                    }
                }

                return strcasecmp(
                    (string) ($left->player?->full_name ?? ''),
                    (string) ($right->player?->full_name ?? ''),
                );
            })
            ->mapWithKeys(fn ($entry): array => [
                $entry->id => ($entry->player?->full_name ?? 'Jugador').' — '.($entry->player?->club?->name ?? 'INDEPENDIENTE'),
            ])->all();
    }

    public function zoneEntryOptions(): array
    {
        if (! $this->competition()) {
            return [];
        }

        return $this->competition()->entries()
            ->whereIn('status', ['eligible', 'assigned'])
            ->with(['player.club', 'player.category'])
            ->get()
            ->sort(function ($left, $right): int {
                $leftRanking = (int) ($left->national_ranking_snapshot ?? 0);
                $rightRanking = (int) ($right->national_ranking_snapshot ?? 0);

                if (($leftRanking > 0) !== ($rightRanking > 0)) {
                    return $leftRanking > 0 ? -1 : 1;
                }

                if ($leftRanking > 0 && $leftRanking !== $rightRanking) {
                    return $leftRanking <=> $rightRanking;
                }

                if ($leftRanking < 1) {
                    $categoryOrder = ((int) ($left->player?->category?->order ?? PHP_INT_MAX))
                        <=> ((int) ($right->player?->category?->order ?? PHP_INT_MAX));
                    if ($categoryOrder !== 0) {
                        return $categoryOrder;
                    }
                }

                return strcasecmp((string) ($left->player?->full_name ?? ''), (string) ($right->player?->full_name ?? ''));
            })
            ->mapWithKeys(fn ($entry): array => [
                $entry->id => ($entry->player?->full_name ?? 'Jugador').' — '.($entry->player?->club?->name ?? 'INDEPENDIENTE'),
            ])->all();
    }

    private function selectedZoneEntryIds(Select $component, int $currentEntryId = 0): array
    {
        $repeater = $component->getParentRepeater();

        return collect($repeater?->getRawState() ?? [])
            ->flatMap(fn (array $zone): array => collect(range(1, 4))
                ->map(fn (int $position): int => (int) ($zone['player_'.$position] ?? 0))
                ->all())
            ->filter()
            ->reject(fn (int $entryId): bool => $entryId === $currentEntryId)
            ->unique()->values()->all();
    }

    public function zoneRowsForSlot(int $slotId): array
    {
        $emptyZone = [
            'name' => 'Zona 1',
            'player_1' => null,
            'player_2' => null,
            'player_3' => null,
            'player_4' => null,
        ];

        if ($slotId < 1 || ! $this->competition()) {
            return [$emptyZone];
        }

        $phase = $this->competition()->phases
            ->first(fn ($phase): bool => $phase->phase_type === 'zones' && $phase->is_enabled);

        if (! $phase) {
            return [$emptyZone];
        }

        $rows = $phase->groups()
            ->where('tournament_slot_id', $slotId)
            ->with(['groupEntries', 'matches:id,group_id,status'])
            ->orderBy('sort_order')
            ->get()
            ->map(function ($group): array {
                $entries = $group->groupEntries->keyBy('position');

                return [
                    'group_id' => $group->id,
                    'locked' => $group->matches->contains(fn ($match): bool => $match->status !== 'pending'),
                    'name' => $group->name,
                    ...collect(range(1, 4))->mapWithKeys(fn (int $position): array => [
                        'player_'.$position => $entries->get($position)?->entry_id,
                    ])->all(),
                ];
            })
            ->values()
            ->all();

        return $rows ?: [$emptyZone];
    }

    public function createZonesAction(): Action
    {
        return Action::make('createZones')
            ->label('Configurar zonas')
            ->icon('heroicon-o-squares-2x2')->color('warning')->size('sm')
            ->fillForm(function (array $arguments): array {
                $slotId = (int) ($arguments['slotId'] ?? 0);
                $competition = $this->competition();

                if ($competition) {
                    app(FiveQuillasCompetitionService::class)->syncApprovedEntries($competition);
                }

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
                    ->visible(fn (Get $get): bool => filled($get('slot_id')))
                    ->table([
                        TableColumn::make('Zona')->width('12%'),
                        TableColumn::make('Posición 1')->width('21%'),
                        TableColumn::make('Posición 2')->width('21%'),
                        TableColumn::make('Posición 3')->width('21%'),
                        TableColumn::make('Posición 4')->width('21%'),
                    ])
                    ->schema([
                        Hidden::make('group_id'),
                        Hidden::make('locked'),
                        TextInput::make('name')->placeholder('Zona 1')->maxLength(255)
                            ->disabled(fn (Get $get): bool => (bool) $get('locked')),
                        ...collect(range(1, 4))->map(fn (int $position) => Select::make('player_'.$position)
                            ->hiddenLabel()
                            ->options($this->zoneEntryOptions())
                            ->searchable()->preload()->native(false)->placeholder('LIBRE')->live()
                            ->transformOptionsForJsUsing(function (array $options, Get $get, Select $component): array {
                                $currentEntryId = (int) ($component->getState() ?? 0);
                                $availableEntryIds = array_map('intval', array_keys(
                                    $this->entryOptionsForSlot((int) $get('../../slot_id')),
                                ));
                                $selectedEntryIds = $this->selectedZoneEntryIds($component, $currentEntryId);

                                return collect($options)
                                    ->filter(fn ($label, $value): bool => (int) $value === $currentEntryId
                                        || (in_array((int) $value, $availableEntryIds, true)
                                            && ! in_array((int) $value, $selectedEntryIds, true)))
                                    ->map(fn ($label, $value): array => [
                                        'label' => $label,
                                        'value' => (string) $value,
                                        'isDisabled' => false,
                                    ])
                                    ->values()->all();
                            })
                            ->disabled(fn (Get $get): bool => (bool) $get('locked')))->all(),
                    ])
                    ->defaultItems(1)->minItems(1)->addActionLabel('Agregar zona')
                    ->reorderable(false)->columnSpanFull(),
            ])
            ->modalHeading('Carga manual de zonas')
            ->modalDescription('Elegí un horario para ver sus zonas. Podés modificar las que todavía no comenzaron y agregar las que falten. Las zonas con partidos iniciados se muestran protegidas.')
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
            ->label('Preparar / actualizar grilla')->icon('heroicon-o-arrow-path')->color('warning')->size('sm')
            ->fillForm(function (array $arguments): array {
                $phase = $this->competition()->phases()->find((int) ($arguments['phaseId'] ?? 0));
                if (! $phase || $phase->seeding_method !== 'number_draw') { return []; }
                return ['draws' => app(FiveQuillasCompetitionService::class)->drawCandidates($phase)
                    ->map(fn ($entry): array => [
                        'entry_id' => $entry->id,
                        'player_name' => $entry->player?->full_name,
                        'draw_number' => $entry->seed,
                    ])->all()];
            })
            ->schema(function (array $arguments): array {
                $phase = $this->competition()->phases()->find((int) ($arguments['phaseId'] ?? 0));
                if (! $phase || $phase->seeding_method !== 'number_draw') { return []; }
                $size = (int) data_get($phase->settings, 'bracket_size', 16);
                return [
                    Repeater::make('draws')->label('Números obtenidos en el sorteo')
                        ->schema([
                            Hidden::make('entry_id'),
                            TextInput::make('player_name')->label('Jugador')->disabled(),
                            TextInput::make('draw_number')->label('Número')->integer()
                                ->minValue(1)->maxValue($size)->required(),
                        ])->columns(2)->addable(false)->deletable(false)->reorderable(false),
                ];
            })
            ->requiresConfirmation()
            ->modalDescription('Vuelve a tomar los clasificados y reconstruye toda la grilla. Si ya cargaste resultados en esta fase, serán eliminados para aplicar las correcciones realizadas en las etapas anteriores. Si la fase se arma por sorteo, cargá aquí el número obtenido por cada jugador.')
            ->action(function (Action $action, array $data, array $arguments): void {
                try {
                    $phase = $this->competition()->phases()->whereKey((int) $arguments['phaseId'])
                        ->where('phase_type', '!=', 'zones')->where('is_enabled', true)->firstOrFail();
                    if ($phase->seeding_method === 'number_draw') {
                        app(FiveQuillasCompetitionService::class)->saveDrawNumbers($phase, $data['draws'] ?? []);
                    }
                    $count = app(FiveQuillasCompetitionService::class)->preparePhase($phase);
                    $assignedPlayers = $phase->matches()->where('round_number', 1)
                        ->get(['player_one_entry_id', 'player_two_entry_id'])
                        ->flatMap(fn ($match): array => [$match->player_one_entry_id, $match->player_two_entry_id])
                        ->filter()->unique()->count();
                    if ($assignedPlayers < 1) {
                        throw ValidationException::withMessages([
                            'phase' => 'No se pudo ubicar ningún clasificado. Revisá que las zonas tengan resultados finalizados.',
                        ]);
                    }
                } catch (ValidationException $exception) {
                    Notification::make()->danger()->title('No se pudo actualizar la grilla')
                        ->body(collect($exception->errors())->flatten()->first() ?? $exception->getMessage())
                        ->persistent()->send();
                    $action->halt();
                    return;
                } catch (\Throwable $exception) {
                    report($exception);
                    Notification::make()->danger()->title('No se pudo actualizar la grilla')
                        ->body('Ocurrió un error al reconstruir los cruces. El detalle quedó registrado para su revisión.')
                        ->persistent()->send();
                    $action->halt();
                    return;
                }
                Notification::make()->success()->title('Fase preparada')
                    ->body($assignedPlayers.' clasificados fueron ubicados en '.$count.' cruces de la grilla.')->send();
            });
    }

    public function configureSlotCrossingsAction(): Action
    {
        return Action::make('configureSlotCrossings')
            ->label('Configurar cruces')->icon('heroicon-o-arrows-right-left')->color('warning')->size('sm')
            ->fillForm(function (array $arguments): array {
                $phase = $this->competition()->phases()->findOrFail((int) ($arguments['phaseId'] ?? 0));
                $rows = $phase->matches()->with(['playerOne', 'playerTwo'])
                    ->orderBy('bracket_order')->get()->map(fn ($match): array => [
                        'slot_id' => $match->playerOne?->source_slot_id,
                        'player_one_entry_id' => $match->player_one_entry_id,
                        'player_two_entry_id' => $match->player_two_entry_id,
                    ])->all();

                return ['crossings' => $rows ?: [['slot_id' => null]]];
            })
            ->schema(function (array $arguments): array {
                $phaseId = (int) ($arguments['phaseId'] ?? 0);
                return [
                Repeater::make('crossings')->label('Enfrentamientos por horario')
                    ->table([
                        TableColumn::make('Horario')->width('24%'),
                        TableColumn::make('1.º o 2.º clasificado')->width('36%'),
                        TableColumn::make('Rival de otra zona')->width('36%'),
                    ])
                    ->schema([
                        Select::make('slot_id')->hiddenLabel()->required()->native(false)->live()
                            ->options(function () use ($phaseId): array {
                                $phase = $this->competition()->phases()->find($phaseId);
                                if (! $phase) { return []; }
                                return app(FiveQuillasCompetitionService::class)->slotEliminationCandidates($phase)
                                    ->groupBy(fn ($row) => (int) $row->group?->tournament_slot_id)
                                    ->map(fn ($rows): string => $rows->first()?->group?->slot?->name ?? 'SIN HORARIO')
                                    ->all();
                            })
                            ->afterStateUpdated(function (Set $set): void {
                                $set('player_one_entry_id', null);
                                $set('player_two_entry_id', null);
                            }),
                        Select::make('player_one_entry_id')->hiddenLabel()->required()->searchable()->preload()->native(false)
                            ->options(fn (Get $get): array => $this->slotQualifiedOptions(
                                $phaseId, (int) ($get('slot_id') ?? 0),
                            ))->live(),
                        Select::make('player_two_entry_id')->hiddenLabel()->required()->searchable()->preload()->native(false)
                            ->options(fn (Get $get): array => $this->slotQualifiedOptions(
                                $phaseId, (int) ($get('slot_id') ?? 0),
                                [(int) ($get('player_one_entry_id') ?? 0)],
                            ))->live(),
                    ])
                    ->defaultItems(1)->minItems(1)->addActionLabel('Agregar cruce')
                    ->reorderable(false)->columnSpanFull(),
                ];
            })
            ->modalHeading('Cruces manuales por horario')
            ->modalDescription('Seleccioná un 1.º y un 2.º clasificado de zonas distintas del mismo horario. Solo se muestran jugadores clasificados en las zonas.')
            ->modalWidth('7xl')->requiresConfirmation()
            ->action(function (array $data, array $arguments): void {
                $phase = $this->competition()->phases()->whereKey((int) ($arguments['phaseId'] ?? 0))
                    ->where('phase_type', 'slot_elimination')->where('is_enabled', true)->firstOrFail();
                $count = app(FiveQuillasCompetitionService::class)
                    ->createManualSlotElimination($phase, $data['crossings'] ?? []);
                Notification::make()->success()->title('Cruces preparados')
                    ->body($count.' enfrentamientos quedaron cargados.')->send();
            });
    }

    public function slotQualifiedOptions(int $phaseId, int $slotId, array $excluded = []): array
    {
        if ($phaseId < 1 || $slotId < 1) { return []; }
        $phase = $this->competition()->phases()->find($phaseId);
        if (! $phase) { return []; }

        return app(FiveQuillasCompetitionService::class)->slotEliminationCandidates($phase)
            ->filter(fn ($row): bool => (int) $row->group?->tournament_slot_id === $slotId)
            ->reject(fn ($row): bool => in_array((int) $row->entry_id, $excluded, true))
            ->sortBy(fn ($row): array => [(int) $row->final_place, (int) $row->entry?->national_ranking_snapshot])
            ->mapWithKeys(fn ($row): array => [
                $row->entry_id => $row->final_place.'.º · '.($row->entry?->player?->full_name ?? 'Jugador')
                    .' — '.($row->group?->name ?? 'Zona'),
            ])->all();
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

    public function editFinalScoreAction(): Action
    {
        return Action::make('editFinalScore')
            ->label('Revisar puntuación')->icon('heroicon-o-pencil-square')->size('xs')->color('warning')
            ->fillForm(function (array $arguments): array {
                $entry = FiveQuillasEntry::query()->with('registration')->find((int) ($arguments['entryId'] ?? 0));
                $key = $entry?->registration?->tournament_instance_id
                    ? 'instance:'.$entry->registration->tournament_instance_id
                    : ($entry?->registration?->result_code ? 'code:'.$entry->registration->result_code : null);
                return ['scoring_rule' => $key];
            })
            ->schema([
                Select::make('scoring_rule')->label('Puntuación correspondiente')->options(fn (): array => $this->scoringRuleOptions())
                    ->searchable()->required(),
            ])->modalHeading('Revisar puntuación del jugador')
            ->action(function (array $data, array $arguments): void {
                $entry = FiveQuillasEntry::query()->where('competition_id', $this->competition()->id)
                    ->findOrFail((int) $arguments['entryId']);
                app(FiveQuillasCompetitionService::class)->assignSelectedScoringRule($entry, (string) $data['scoring_rule']);
                Notification::make()->success()->title('Puntuación actualizada')->send();
            });
    }

    public function saveFinalScoringAction(): Action
    {
        return Action::make('saveFinalScoring')
            ->label('Guardar puntuación')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->size('sm')
            ->visible(fn (): bool => (bool) $this->record->type?->assigns_points
                && $this->competition()?->status === 'completed'
                && $this->competition()?->entries()->whereNotNull('final_position')->exists())
            ->requiresConfirmation()
            ->modalHeading('Guardar la puntuación definitiva')
            ->modalDescription('Se aplicará la tabla configurada para el tipo de torneo según las posiciones verificadas. Las inscripciones que ya tengan puntuación serán omitidas para evitar duplicados.')
            ->action(function (): void {
                $summary = app(FiveQuillasCompetitionService::class)
                    ->commitFinalScoring($this->competition());
                Notification::make()->success()->title('Puntuación procesada')->body(sprintf(
                    '%d guardadas · %d ya existentes · %d sin regla · %d sin inscripción.',
                    $summary['assigned'],
                    $summary['existing'],
                    $summary['without_rule'],
                    $summary['without_registration'],
                ))->send();
            });
    }

    public function proposedFinalPoints(FiveQuillasEntry $entry): ?float
    {
        return app(FiveQuillasCompetitionService::class)
            ->proposedPointsForPlacement($this->record, (int) $entry->final_position);
    }

    private function scoringRuleOptions(): array
    {
        $tournament = $this->record->loadMissing('type');
        return collect(app(\App\Services\TournamentScoringService::class)->getRules($tournament))
            ->mapWithKeys(function (array $rule): array {
                $key = ! empty($rule['tournament_instance_id'])
                    ? 'instance:'.$rule['tournament_instance_id']
                    : (! empty($rule['code']) ? 'code:'.$rule['code'] : null);
                if (! $key) { return []; }
                return [$key => trim(($rule['description'] ?? $rule['code'] ?? 'Resultado').' · '.($rule['points'] ?? 0).' puntos')];
            })->all();
    }

    public function finalInstanceLabel(FiveQuillasEntry $entry): string
    {
        $competition = $this->competition();
        $lastMatch = $competition?->phases->where('phase_type', '!=', 'zones')->flatMap->matches
            ->filter(fn ($match): bool => in_array((int) $entry->id, [
                (int) $match->player_one_entry_id, (int) $match->player_two_entry_id,
            ], true))->sortByDesc(fn ($match): string => sprintf('%05d-%05d', $match->phase?->sort_order ?? 0, $match->round_number))->first();
        if ($lastMatch) {
            return preg_replace('/\s+\d+$/', '', (string) $lastMatch->label) ?: ($lastMatch->phase?->name ?? 'Eliminación');
        }
        $zoneRow = $competition?->phases->where('phase_type', 'zones')->flatMap->groups->flatMap->groupEntries
            ->firstWhere('entry_id', $entry->id);
        return $zoneRow ? 'Zonas · '.$zoneRow->final_place.'.º puesto' : 'Sin instancia';
    }

    public function finalPerformance(FiveQuillasEntry $entry): array
    {
        $performance = app(FiveQuillasCompetitionService::class)
            ->finalStandingPerformance($this->competition(), (int) $entry->id);

        return [
            'sets_difference' => $performance['sets_difference'],
            'average' => number_format($performance['average'], 3, ',', '.'),
        ];
    }

    public function recordResultAction(): Action
    {
        return Action::make('recordResult')
            ->label('Cargar / editar resultado')
            ->icon('heroicon-o-pencil-square')->color('success')->size('xs')
            ->iconButton()->tooltip('Cargar o editar resultado')
            ->fillForm(function (array $arguments): array {
                $match = $this->competitionMatch((int) ($arguments['matchId'] ?? 0));
                $storedScores = is_array($match?->set_scores) ? $match->set_scores : [];
                $storedSets = $storedScores['sets'] ?? (array_is_list($storedScores) ? $storedScores : []);
                $sets = collect(is_array($storedSets) ? $storedSets : [])
                    ->filter(fn ($set): bool => is_array($set))
                    ->map(fn ($set): array => [
                        'player_one_points' => isset($set['player_one_points']) ? (int) $set['player_one_points'] : null,
                        'player_two_points' => isset($set['player_two_points']) ? (int) $set['player_two_points'] : null,
                    ])->values()->all();

                return [
                    'players_label' => $this->matchPlayersLabel((int) ($arguments['matchId'] ?? 0)),
                    'player_one_name' => $match?->playerOne?->player?->full_name ?? 'Jugador 1',
                    'player_two_name' => $match?->playerTwo?->player?->full_name ?? 'Jugador 2',
                    'declare_without_scores' => (bool) data_get($match?->set_scores, 'manual_winner', false),
                    'declared_winner_id' => data_get($match?->set_scores, 'manual_winner') ? $match?->winner_entry_id : null,
                    ...collect(range(1, max(1, (int) ($match?->phase?->sets_to_play ?? 3))))
                        ->flatMap(fn (int $setNumber): array => [
                            'player_one_set_'.$setNumber => data_get($sets, ($setNumber - 1).'.player_one_points'),
                            'player_two_set_'.$setNumber => data_get($sets, ($setNumber - 1).'.player_two_points'),
                        ])->all(),
                ];
            })
            ->schema(function (array $arguments): array {
                $match = $this->competitionMatch((int) ($arguments['matchId'] ?? 0));
                $bestOf = max(1, (int) ($match?->phase?->sets_to_play ?? 3));
                $pointsPerSet = max(1, (int) ($match?->phase?->points_per_set ?? 60));
                $inputStyle = 'width:4.25rem;max-width:4.25rem;text-align:center;padding-left:.35rem;padding-right:.35rem';
                $playerNames = [
                    1 => $match?->playerOne?->player?->full_name ?? 'Jugador 1',
                    2 => $match?->playerTwo?->player?->full_name ?? 'Jugador 2',
                ];

                $headers = [
                    Placeholder::make('player_header')->hiddenLabel()->content('Jugador')
                        ->columnSpan(3)->extraAttributes(['style' => 'font-weight:800']),
                    ...collect(range(1, $bestOf))->map(fn (int $setNumber) =>
                        Placeholder::make('set_header_'.$setNumber)->hiddenLabel()->content('Set '.$setNumber)
                            ->extraAttributes(['style' => 'text-align:center;font-size:.75rem;font-weight:800'])
                    )->all(),
                ];

                $playerRow = function (int $playerNumber) use ($bestOf, $pointsPerSet, $inputStyle, $playerNames): array {
                    $playerKey = $playerNumber === 1 ? 'one' : 'two';

                    return [
                        Placeholder::make('player_'.$playerNumber.'_label')->hiddenLabel()
                            ->content($playerNames[$playerNumber])
                            ->columnSpan(3)->extraAttributes(['style' => 'font-weight:800;align-self:center']),
                        ...collect(range(1, $bestOf))->map(fn (int $setNumber) =>
                            TextInput::make('player_'.$playerKey.'_set_'.$setNumber)
                                ->hiddenLabel()->numeric()->minValue(0)->maxValue($pointsPerSet)
                                ->nullable()
                                ->extraInputAttributes(['style' => $inputStyle, 'inputmode' => 'numeric'])
                        )->all(),
                    ];
                };

                return [
                    Hidden::make('players_label'),
                    Hidden::make('player_one_name'),
                    Hidden::make('player_two_name'),
                    Toggle::make('declare_without_scores')->label('Proclamar ganador sin informar los resultados de los sets')
                        ->live()->visible(fn (): bool => ! (bool) $match?->group_id)->columnSpanFull(),
                    Select::make('declared_winner_id')->label('Ganador de la partida')
                        ->options(array_filter([
                            $match?->player_one_entry_id => $playerNames[1],
                            $match?->player_two_entry_id => $playerNames[2],
                        ]))->required(fn (Get $get): bool => (bool) $get('declare_without_scores'))
                        ->visible(fn (Get $get): bool => (bool) $get('declare_without_scores'))->columnSpanFull(),
                    Grid::make($bestOf + 3)
                        ->schema([...$headers, ...$playerRow(1), ...$playerRow(2)])
                        ->visible(fn (Get $get): bool => ! (bool) $get('declare_without_scores'))
                        ->columnSpanFull(),
                ];
            })
            ->modalHeading(fn (array $arguments): string => 'Resultado · '.$this->matchPlayersLabel((int) ($arguments['matchId'] ?? 0)))
            ->modalDescription('Cargá los sets que ya finalizaron o, en la Etapa Final, elegí la opción para proclamar directamente al ganador sin informar los resultados de los sets.')
            ->modalWidth('3xl')
            ->action(function (array $data, array $arguments, Action $action): void {
                $match = FiveQuillasMatch::query()
                    ->whereHas('phase', fn ($query) => $query->where('competition_id', $this->competition()->id))
                    ->whereNotNull('player_one_entry_id')->whereNotNull('player_two_entry_id')->where('is_bye', false)
                    ->findOrFail((int) $arguments['matchId']);
                if ((bool) ($data['declare_without_scores'] ?? false)) {
                    try {
                        $updatedMatch = app(FiveQuillasCompetitionService::class)->declareMatchWinner(
                            $match, (int) ($data['declared_winner_id'] ?? 0),
                        );
                    } catch (ValidationException $exception) {
                        Notification::make()->danger()->title('No se pudo proclamar al ganador')
                            ->body(collect($exception->errors())->flatten()->first() ?? 'Revisá el jugador seleccionado.')
                            ->persistent()->send();
                        $action->halt();
                        return;
                    }
                    Notification::make()->success()->title('Ganador proclamado')
                        ->body('El jugador avanzó al cruce siguiente sin registrar resultados por set.')->send();
                    return;
                }
                $scores = collect(range(1, max(1, (int) $match->phase()->value('sets_to_play'))))
                    ->map(function (int $setNumber) use ($data): array {
                        $one = $data['player_one_set_'.$setNumber] ?? null;
                        $two = $data['player_two_set_'.$setNumber] ?? null;
                        $one = is_string($one) ? trim($one) : $one;
                        $two = is_string($two) ? trim($two) : $two;
                        $oneFilled = $one !== null && $one !== '';
                        $twoFilled = $two !== null && $two !== '';
                        if ($oneFilled !== $twoFilled) {
                            throw ValidationException::withMessages([
                                'scores' => 'Completá los dos puntajes del set '.$setNumber.' o dejá ambos vacíos.',
                            ]);
                        }

                        return [
                            'player_one_points' => $oneFilled ? (int) $one : null,
                            'player_two_points' => $twoFilled ? (int) $two : null,
                        ];
                    })
                    ->filter(fn (array $set): bool => $set['player_one_points'] !== null)
                    ->values()
                    ->all();
                try {
                    $updatedMatch = app(FiveQuillasCompetitionService::class)->recordMatchResult($match, $scores);
                } catch (ValidationException $exception) {
                    Notification::make()->danger()
                        ->title('No se pudo guardar el resultado')
                        ->body(collect($exception->errors())->flatten()->first() ?? 'Revisá los puntajes ingresados.')
                        ->persistent()
                        ->send();
                    $action->halt();

                    return;
                } catch (\Throwable $exception) {
                    report($exception);
                    Notification::make()->danger()
                        ->title('No se pudo guardar el resultado')
                        ->body('Ocurrió un error al procesar el partido. El detalle quedó registrado para su revisión.')
                        ->persistent()
                        ->send();
                    $action->halt();

                    return;
                }
                Notification::make()->success()
                    ->title($updatedMatch->status === 'completed' ? 'Resultado final guardado' : 'Resultado parcial guardado')
                    ->body($updatedMatch->status === 'completed'
                        ? 'El ganador, la clasificación y la progresión del cuadro fueron actualizados.'
                        : 'La partida continúa en curso y el resultado ya está disponible para el público.')
                    ->send();
            });
    }

    public function resetResultAction(): Action
    {
        return Action::make('resetResult')
            ->label('Reiniciar partido')
            ->icon('heroicon-o-arrow-path')
            ->color('danger')
            ->size('xs')
            ->iconButton()
            ->tooltip('Reiniciar partido')
            ->requiresConfirmation()
            ->modalHeading('Reiniciar partido')
            ->modalDescription('Se eliminará el resultado y el partido volverá al estado No jugado. Esta acción también retira a los jugadores de los cruces posteriores que todavía no comenzaron.')
            ->action(function (array $arguments): void {
                $match = FiveQuillasMatch::query()
                    ->whereHas('phase', fn ($query) => $query->where('competition_id', $this->competition()->id))
                    ->whereIn('status', ['in_progress', 'completed'])
                    ->findOrFail((int) $arguments['matchId']);

                try {
                    $resetMatch = app(FiveQuillasCompetitionService::class)->resetMatchResult($match);
                } catch (ValidationException $exception) {
                    Notification::make()->danger()->title('No se puede reiniciar el partido')
                        ->body(collect($exception->errors())->flatten()->first() ?? $exception->getMessage())
                        ->send();

                    return;
                }

                if ($resetMatch->status !== 'pending' || $resetMatch->winner_entry_id !== null) {
                    Notification::make()->danger()->title('No se pudo reiniciar el partido')
                        ->body('El resultado continúa guardado. Volvé a intentarlo y revisá el registro del sistema si el problema persiste.')
                        ->send();

                    return;
                }
                Notification::make()->success()->title('Partido reiniciado')
                    ->body('El resultado fue eliminado y el partido quedó como no jugado.')->send();
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
            ->with(['phase', 'playerOne.player', 'playerTwo.player'])
            ->find($matchId);
    }

    public function callMatchAction(): Action
    {
        return Action::make('callMatch')
            ->label('Llamar')
            ->icon('heroicon-o-megaphone')
            ->color('info')
            ->size('xs')
            ->iconButton()
            ->tooltip('Llamar partido')
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
        if ($competition) {
            app(FiveQuillasCompetitionService::class)->ensureFinalStandingsOrder($competition);
            $competition = $this->competition();
        }
        $entries = $competition?->entries->where('status', '!=', 'withdrawn') ?? collect();
        $logoPath = $this->record->publicationLogoPath();
        $start = $this->record->start_date;
        $end = $this->record->end_date;
        $championEntry = $competition?->entries->firstWhere('final_position', 1)
            ?: $competition?->phases->where('phase_type', '!=', 'zones')->sortByDesc('sort_order')
                ->flatMap->matches->where('status', 'completed')->sortByDesc('round_number')->first()?->winner;
        $championPhoto = $championEntry?->player?->photo_path;

        return [
            'competition' => $competition,
            'entriesBySlot' => $entries->groupBy(fn ($entry): string => $entry->sourceSlot?->name ?? 'SIN HORARIO ASIGNADO'),
            'entriesByPhase' => $entries->where('status', 'eligible')
                ->groupBy(fn ($entry): string => $entry->entryPhase?->name ?? 'PRIMERA FASE'),
            'publicJsonUrl' => $competition?->is_public
                ? route('five-quillas-competition.public', ['slug' => $competition->public_slug])
                : null,
            'publicPageUrl' => $competition?->is_public
                ? route('five-quillas-competition.page', ['slug' => $competition->public_slug])
                : null,
            'tournamentInfo' => [
                'name' => $this->record->name,
                'venue' => $this->record->venueName(),
                'date' => $start
                    ? ($end && ! $end->isSameDay($start)
                        ? $start->format('d/m/Y').' al '.$end->format('d/m/Y')
                        : $start->format('d/m/Y'))
                    : 'Sin fecha',
                'type' => $this->record->type?->name ?? 'Sin tipo',
                'official' => $this->record->type?->is_official ? 'Oficial' : 'No oficial',
                'modality' => $this->record->tournamentModalities->pluck('modality.name')->filter()->implode(', ') ?: 'Sin modalidad',
                'logo' => $logoPath ? Storage::disk('public_path')->url($logoPath) : null,
            ],
            'champion' => $championEntry?->player ? [
                'name' => $championEntry->player->full_name,
                'photo' => $championPhoto ? Storage::disk('public_path')->url($championPhoto) : null,
            ] : null,
        ];
    }
}
