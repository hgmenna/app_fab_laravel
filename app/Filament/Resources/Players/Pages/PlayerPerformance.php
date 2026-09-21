<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Helpers\FabPath;
use App\Models\Discipline;
use App\Models\TournamentRegistration;
use App\Models\TournamentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\Page;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PlayerPerformance extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithRecord;

    protected static string $resource = PlayerResource::class;

    protected string $view = 'filament.resources.players.pages.player-performance';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return 'Desempeño: ' . $this->record->full_name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $rows = $this->getFilteredSortedTableQuery()->with([
                        'tournament' => fn ($query) => $query
                            ->withCount('registrations')
                            ->with(['type', 'discipline']),
                        'tournamentInstance',
                    ])->get();

                    $pdf = Pdf::loadView('pdf.player-performance', [
                        'player' => $this->record,
                        'rows' => $rows,
                        'points' => $rows->sum(fn ($row) => (float) $row->points),
                        'generatedAt' => now()->format('d/m/Y H:i'),
                        'logo' => is_file(FabPath::logo()) ? FabPath::logo() : public_path(config('fab.paths.logo')),
                        'footer_image' => is_file(FabPath::footer()) ? FabPath::footer() : public_path(config('fab.paths.footer')),
                    ])->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'desempeno-jugador-' . $this->record->id . '.pdf'
                    );
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => TournamentRegistration::query()
                ->where('player_id', $this->record->id)
                ->whereHas('tournament', fn (Builder $query) => $query->whereDate('end_date', '<=', today()))
                ->addSelect(['participant_count' => DB::table('tournament_registrations as participant_counts')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('participant_counts.tournament_id', 'tournament_registrations.tournament_id')])
                ->with([
                    'tournament' => fn ($query) => $query
                        ->withCount('registrations')
                        ->with(['type', 'discipline']),
                    'tournamentInstance',
                ]))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('tournament.name')->label('Torneo')->wrap(),
                TextColumn::make('tournament.type.name')->label('Tipo')->wrap(),
                TextColumn::make('tournament.end_date')->label('Fecha')->date('d/m/Y'),
                TextColumn::make('participant_count')->label('Inscriptos')->numeric(),
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

    public function totals(): array
    {
        $query = $this->getFilteredTableQuery();

        return [
            'tournaments' => (clone $query)->count(),
            'points' => (clone $query)->sum('points'),
            'results' => (clone $query)->where(function (Builder $query) {
                $query->whereNotNull('result_code')->orWhereNotNull('tournament_instance_id');
            })->count(),
        ];
    }
}
