<?php

namespace App\Filament\Resources\Tournaments\Pages;

use App\Filament\Resources\Tournaments\TournamentResource;
use App\Models\FiveQuillasCompetition;
use App\Services\FiveQuillasCompetitionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
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
                        'seeding_method' => $phase->seeding_method,
                        'split_by_slot' => $phase->split_by_slot,
                        'is_enabled' => $phase->is_enabled ?? true,
                        'qualifiers' => data_get($phase->settings, 'qualifiers'),
                        'tables' => data_get($phase->settings, 'tables'),
                        'estimated_minutes' => data_get($phase->settings, 'estimated_minutes'),
                        'rest_minutes' => data_get($phase->settings, 'rest_minutes', 0),
                        'start_time' => data_get($phase->settings, 'start_time'),
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
                            ])->required()->native(false)->columnSpan(2),
                            TextInput::make('sets_to_play')->label('Sets')->integer()->minValue(1)->default(3)->required(),
                            TextInput::make('points_per_set')->label('Puntos por set')->integer()->minValue(1)->default(60)->required(),
                            Select::make('seeding_method')->label('Armado')->options([
                                'previous_results' => 'Resultados de la fase anterior',
                                'national_ranking' => 'Ranking nacional',
                                'automatic_draw' => 'Sorteo automático',
                                'number_draw' => 'Sorteo de número de grilla',
                                'manual' => 'Carga manual',
                            ])->required()->native(false)->columnSpan(2),
                            TextInput::make('qualifiers')->label('Clasificados')->integer()->minValue(1),
                            TextInput::make('tables')->label('Mesas disponibles')->integer()->minValue(1),
                            TextInput::make('estimated_minutes')->label('Duración por partido')->suffix('min')->integer()->minValue(1),
                            TextInput::make('rest_minutes')->label('Descanso entre partidos')->suffix('min')->integer()->minValue(0)->default(0),
                            TimePicker::make('start_time')->label('Hora de inicio')->seconds(false),
                            Toggle::make('split_by_slot')->label('Separar por horarios')->inline(false),
                            Toggle::make('is_enabled')->label('Fase habilitada')->default(true)->inline(false),
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
                ->modalDescription('El orden de las filas determina el orden de disputa. La fase de zonas debe existir una sola vez. Las fases con resultados cargados no se eliminan: quedan deshabilitadas para preservar el historial.')
                ->modalWidth('7xl')
                ->action(function (array $data): void {
                    $enabled = app(FiveQuillasCompetitionService::class)->configureFormat(
                        $this->competition(),
                        $data['phases'] ?? [],
                    );
                    Notification::make()->success()->title('Formato actualizado')
                        ->body($enabled.' fases habilitadas para este torneo.')->send();
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
