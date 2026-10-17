<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\FiveQuillasCompetition;
use App\Models\FiveQuillasMatch;
use App\Services\FiveQuillasCompetitionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
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
                ->label(fn (): string => $this->competition()?->is_public ? 'Ocultar publicación' : 'Publicar desarrollo')
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
                    Notification::make()->success()->title($publish ? 'Desarrollo publicado' : 'Desarrollo ocultado')->send();
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
                        'tables' => data_get($phase->settings, 'tables'),
                        'estimated_minutes' => data_get($phase->settings, 'estimated_minutes'),
                        'rest_minutes' => data_get($phase->settings, 'rest_minutes', 0),
                        'schedules' => data_get($phase->settings, 'schedules', []),
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
                            TextInput::make('qualifiers')->label('Clasificados a la siguiente fase')->integer()->minValue(1)
                                ->helperText('En la última fase se ignora porque allí finaliza el torneo.'),
                            TextInput::make('tables')->label('Mesas disponibles')->integer()->minValue(1),
                            TextInput::make('estimated_minutes')->label('Duración por partido')->suffix('min')->integer()->minValue(1),
                            TextInput::make('rest_minutes')->label('Descanso entre partidos')->suffix('min')->integer()->minValue(0)->default(0),
                            Toggle::make('split_by_slot')->label('Usar los horarios definidos en el torneo')->inline(false)
                                ->helperText('Cada horario conserva sus propios inscriptos, zonas y partidos.'),
                            Toggle::make('is_enabled')->label('Fase habilitada')->default(true)->inline(false),
                            Repeater::make('schedules')
                                ->label('Horarios propios de esta fase')
                                ->helperText('Usalo en fases posteriores a las zonas. Primera Nacional puede dividirse en dos horarios independientes.')
                                ->table([
                                    TableColumn::make('Nombre')->markAsRequired(),
                                    TableColumn::make('Inicio'),
                                    TableColumn::make('Sector de la grilla'),
                                    TableColumn::make('Mesas'),
                                ])
                                ->schema([
                                    TextInput::make('name')->hiddenLabel()->required()->maxLength(100),
                                    DateTimePicker::make('starts_at')->hiddenLabel()->seconds(false),
                                    TextInput::make('bracket_section')->hiddenLabel()->placeholder('Ej. 1-64 a 29-4')->maxLength(100),
                                    TextInput::make('tables')->hiddenLabel()->integer()->minValue(1),
                                ])
                                ->visible(fn (Get $get): bool => $get('phase_type') !== 'zones')
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
            Action::make('applyTypeTemplate')
                ->label('Aplicar plantilla del torneo')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(fn (): bool => $this->competition() !== null)
                ->requiresConfirmation()
                ->modalHeading('Aplicar plantilla del tipo de torneo')
                ->modalDescription('Se reemplazará el formato actual por la plantilla correspondiente al tipo de torneo. Solo se permite antes de cargar zonas o partidos.')
                ->action(function (): void {
                    $count = app(FiveQuillasCompetitionService::class)->applyTournamentTypeTemplate($this->competition());
                    Notification::make()->success()->title('Plantilla aplicada')
                        ->body($count.' fases creadas según el tipo de torneo.')->send();
                }),
            Action::make('createZones')
                ->label('Cargar zonas')
                ->icon('heroicon-o-squares-2x2')
                ->color('warning')
                ->visible(fn (): bool => $this->competition() !== null)
                ->schema([
                    Select::make('slot_id')
                        ->label('Horario')
                        ->options(fn (): array => $this->record->slots()->where('is_active', true)->orderBy('starts_at')->pluck('name', 'id')->all())
                        ->required()
                        ->searchable()
                        ->live(),
                    Repeater::make('zones')
                        ->label('Zonas del horario')
                        ->table([
                            TableColumn::make('Zona'),
                            TableColumn::make('Posición 1'),
                            TableColumn::make('Posición 2'),
                            TableColumn::make('Posición 3'),
                            TableColumn::make('Posición 4'),
                        ])
                        ->schema([
                            TextInput::make('name')->placeholder('Zona 1')->maxLength(255),
                            ...collect(range(1, 4))->map(fn (int $position) => Select::make('player_'.$position)
                                ->hiddenLabel()
                                ->options(fn (Get $get): array => $this->entryOptionsForSlot((int) $get('../../slot_id')))
                                ->searchable()->preload()->native(false)
                                ->placeholder('LIBRE'))->all(),
                        ])
                        ->defaultItems(1)
                        ->minItems(1)
                        ->addActionLabel('Agregar zona')
                        ->reorderable(false)
                        ->columnSpanFull(),
                ])
                ->modalHeading('Carga manual de zonas')
                ->modalDescription('Elegí únicamente jugadores aprobados del horario. Cada zona admite tres o cuatro jugadores; cualquier posición sin jugador queda LIBRE y produce el pase automático previsto en la planilla oficial.')
                ->modalWidth('7xl')
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    $count = app(FiveQuillasCompetitionService::class)->createManualZones(
                        $this->competition(),
                        (int) $data['slot_id'],
                        $data['zones'] ?? [],
                    );
                    Notification::make()->success()->title('Zonas guardadas')->body($count.' zonas quedaron preparadas para revisión.')->send();
                }),
            Action::make('recordResult')
                ->label('Cargar resultado')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->visible(fn (): bool => $this->competition()?->phases()->whereHas('matches', fn ($query) => $query
                    ->whereNotNull('player_one_entry_id')->whereNotNull('player_two_entry_id')->where('is_bye', false))->exists() ?? false)
                ->schema([
                    Select::make('match_id')->label('Partido')->options(fn (): array => $this->matchOptions())
                        ->searchable()->preload()->native(false)->required(),
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
                ->modalDescription('Cargá solamente los sets realmente disputados. El sistema determina el ganador, normaliza los sets computables y recalcula la zona.')
                ->action(function (array $data): void {
                    $match = FiveQuillasMatch::query()
                        ->whereHas('phase', fn ($query) => $query->where('competition_id', $this->competition()->id))
                        ->findOrFail((int) $data['match_id']);
                    app(FiveQuillasCompetitionService::class)->recordMatchResult($match, $data['scores'] ?? []);
                    Notification::make()->success()->title('Resultado guardado')->body('Clasificación y promedio actualizados.')->send();
                }),
        ];
    }

    public function competition(): ?FiveQuillasCompetition
    {
        return FiveQuillasCompetition::query()
            ->where('tournament_id', $this->record->id)
            ->with(['phases.groups.slot', 'phases.groups.groupEntries.entry.player.club', 'phases.groups.matches.playerOne.player', 'phases.groups.matches.playerTwo.player', 'entries.player.club', 'entries.sourceSlot'])
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

    public function matchOptions(): array
    {
        if (! $this->competition()) {
            return [];
        }
        return FiveQuillasMatch::query()
            ->whereHas('phase', fn ($query) => $query->where('competition_id', $this->competition()->id)->where('is_enabled', true))
            ->whereNotNull('player_one_entry_id')->whereNotNull('player_two_entry_id')->where('is_bye', false)
            ->with(['phase', 'group', 'playerOne.player', 'playerTwo.player'])
            ->orderBy('phase_id')->orderBy('round_number')->orderBy('bracket_order')->get()
            ->mapWithKeys(fn ($match): array => [$match->id => implode(' · ', array_filter([
                $match->phase?->name,
                $match->group?->name,
                $match->label,
                ($match->playerOne?->player?->full_name ?? 'A definir').' vs '.($match->playerTwo?->player?->full_name ?? 'A definir'),
                $match->status === 'completed' ? 'RESULTADO CARGADO' : null,
            ]))])->all();
    }

    protected function getViewData(): array
    {
        $competition = $this->competition();
        $entries = $competition?->entries->where('status', '!=', 'withdrawn') ?? collect();

        return [
            'competition' => $competition,
            'entriesBySlot' => $entries->groupBy(fn ($entry): string => $entry->sourceSlot?->name ?? 'SIN HORARIO ASIGNADO'),
            'publicJsonUrl' => $competition?->is_public
                ? route('five-quillas-competition.public', ['slug' => $competition->public_slug])
                : null,
        ];
    }
}
