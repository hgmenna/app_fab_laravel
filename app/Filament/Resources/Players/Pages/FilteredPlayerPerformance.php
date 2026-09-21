<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Models\Discipline;
use App\Models\Player;
use App\Models\TournamentRegistration;
use App\Models\TournamentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;

class FilteredPlayerPerformance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = PlayerResource::class;

    protected static ?string $title = 'Desempeño de jugadores filtrados';

    protected string $view = 'filament.resources.players.pages.filtered-player-performance';

    #[Locked]
    public array $playerIds = [];

    public function mount(string $report): void
    {
        $selection = session("player-performance-reports.{$report}");

        abort_unless(
            is_array($selection) && ($selection['user_id'] ?? null) === Auth::id()
                && is_array($selection['player_ids'] ?? null),
            404
        );

        $this->playerIds = array_map('intval', $selection['player_ids']);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    ini_set('memory_limit', '512M');
                    set_time_limit(300);

                    $rows = $this->getFilteredSortedTableQuery()
                        ->with([
                            'player',
                            'tournament' => fn ($query) => $query->withCount('registrations')->with('type'),
                            'tournamentInstance',
                        ])
                        ->get();

                    $pdf = Pdf::loadView('pdf.filtered-player-performance', [
                        'players' => $this->playerSummaries(),
                        'rows' => $rows,
                        'totals' => $this->totals(),
                        'generatedAt' => now()->format('d/m/Y H:i'),
                    ])->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'desempeno-jugadores-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => TournamentRegistration::query()
                ->whereIn('player_id', $this->playerIds)
                ->whereHas('tournament', fn (Builder $query) => $query->whereDate('end_date', '<=', today()))
                ->with([
                    'player',
                    'tournament' => fn ($query) => $query->withCount('registrations')->with('type'),
                    'tournamentInstance',
                ]))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('player_name')
                    ->label('Jugador')
                    ->state(fn (TournamentRegistration $record): string => $record->player?->full_name ?? '-')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHas('player', fn (Builder $player) => $player
                            ->where('last_name', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")))
                    ->wrap(),
                TextColumn::make('tournament.name')->label('Torneo')->wrap(),
                TextColumn::make('tournament.type.name')->label('Tipo')->wrap(),
                TextColumn::make('tournament.end_date')->label('Fecha')->date('d/m/Y'),
                TextColumn::make('tournament.registrations_count')->label('Inscriptos')->numeric(),
                TextColumn::make('position')
                    ->label('Posición / resultado')
                    ->state(fn (TournamentRegistration $record): string =>
                        $record->result_description
                        ?? $record->tournamentInstance?->description
                        ?? 'Sin resultado'),
                TextColumn::make('points')->label('Puntos')->numeric(decimalPlaces: 2),
            ])
            ->filters([
                SelectFilter::make('discipline')
                    ->label('Disciplina')
                    ->options(fn () => Discipline::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => empty($data['value'])
                        ? $query
                        : $query->whereHas('tournament', fn (Builder $tournament) => $tournament->where('discipline_id', $data['value']))),
                SelectFilter::make('type')
                    ->label('Tipo de torneo')
                    ->options(fn () => TournamentType::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => empty($data['value'])
                        ? $query
                        : $query->whereHas('tournament', fn (Builder $tournament) => $tournament->where('tournament_type_id', $data['value']))),
                Filter::make('dates')
                    ->label('Fechas del torneo')
                    ->schema([
                        DatePicker::make('from')->label('Desde'),
                        DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! empty($data['from'])) {
                            $query->whereHas('tournament', fn (Builder $tournament) => $tournament->whereDate('end_date', '>=', $data['from']));
                        }
                        if (! empty($data['until'])) {
                            $query->whereHas('tournament', fn (Builder $tournament) => $tournament->whereDate('end_date', '<=', $data['until']));
                        }

                        return $query;
                    }),
            ]);
    }

    public function playerSummaries(): \Illuminate\Support\Collection
    {
        $performance = (clone $this->getFilteredTableQuery())
            ->reorder()
            ->select('player_id')
            ->selectRaw('COUNT(*) as tournaments_count, COUNT(CASE WHEN result_code IS NOT NULL OR tournament_instance_id IS NOT NULL THEN 1 END) as results_count, COALESCE(SUM(points), 0) as points_total')
            ->groupBy('player_id')
            ->get()
            ->keyBy('player_id');

        return Player::query()
            ->whereIn('id', $this->playerIds)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name'])
            ->map(fn (Player $player): array => [
                'name' => $player->full_name,
                'tournaments' => (int) ($performance->get($player->id)?->tournaments_count ?? 0),
                'results' => (int) ($performance->get($player->id)?->results_count ?? 0),
                'points' => (float) ($performance->get($player->id)?->points_total ?? 0),
            ]);
    }

    public function totals(): array
    {
        $players = $this->playerSummaries();

        return [
            'players' => $players->count(),
            'tournaments' => $players->sum('tournaments'),
            'results' => $players->sum('results'),
            'points' => $players->sum('points'),
        ];
    }
}
