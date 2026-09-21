<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Helpers\FabPath;
use App\Models\Discipline;
use App\Models\Player;
use App\Models\TournamentRegistration;
use App\Models\TournamentType;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;

class FilteredPlayerPerformance extends Page
{
    protected static string $resource = PlayerResource::class;

    protected static ?string $title = 'Desempeño de jugadores filtrados';

    protected string $view = 'filament.resources.players.pages.filtered-player-performance';

    #[Locked]
    public array $playerIds = [];

    public string $searchPlayer = '';
    public string $disciplineId = '';
    public string $typeId = '';
    public string $fromDate = '';
    public string $untilDate = '';
    public bool $onlyParticipants = false;

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

    public function disciplineOptions(): array
    {
        return Discipline::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function typeOptions(): array
    {
        return TournamentType::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * La pantalla y el PDF comparten exactamente la misma selección.
     */
    public function reportData(): array
    {
        $players = Player::query()
            ->whereIn('id', $this->playerIds)
            ->when(trim($this->searchPlayer) !== '', function (Builder $query): void {
                $search = trim($this->searchPlayer);
                $query->where(fn (Builder $names) => $names
                    ->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%"));
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name']);

        $rowsByPlayer = TournamentRegistration::query()
            ->whereIn('player_id', $players->pluck('id'))
            ->whereHas('tournament', function (Builder $tournament): void {
                $tournament->whereDate('end_date', '<=', today());

                if ($this->disciplineId !== '') {
                    $tournament->where('discipline_id', $this->disciplineId);
                }
                if ($this->typeId !== '') {
                    $tournament->where('tournament_type_id', $this->typeId);
                }
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->fromDate)) {
                    $tournament->whereDate('end_date', '>=', $this->fromDate);
                }
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->untilDate)) {
                    $tournament->whereDate('end_date', '<=', $this->untilDate);
                }
            })
            ->addSelect(['participant_count' => DB::table('tournament_registrations as participant_counts')
                ->selectRaw('COUNT(*)')
                ->whereColumn('participant_counts.tournament_id', 'tournament_registrations.tournament_id')])
            ->with(['tournament.type', 'tournamentInstance'])
            ->orderByDesc('id')
            ->get()
            ->groupBy('player_id');

        $summaries = $players->map(function (Player $player) use ($rowsByPlayer): array {
            $rows = $rowsByPlayer->get($player->id, collect());

            return [
                'id' => $player->id,
                'name' => $player->full_name,
                'tournaments' => $rows->count(),
                'results' => $rows->filter(fn (TournamentRegistration $row): bool =>
                    $row->result_code !== null || $row->tournament_instance_id !== null)->count(),
                'points' => $rows->sum(fn (TournamentRegistration $row): float => (float) $row->points),
                'rows' => $rows,
            ];
        })->when(
            $this->onlyParticipants,
            fn ($summaries) => $summaries->filter(fn (array $player): bool => $player['tournaments'] > 0)->values()
        );

        return [
            'players' => $summaries,
            'totals' => [
                'players' => $summaries->count(),
                'tournaments' => $summaries->sum('tournaments'),
                'results' => $summaries->sum('results'),
                'points' => $summaries->sum('points'),
            ],
        ];
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

                    $report = $this->reportData();
                    $pdf = Pdf::loadView('pdf.filtered-player-performance', [
                        'players' => $report['players'],
                        'totals' => $report['totals'],
                        'generatedAt' => now()->format('d/m/Y H:i'),
                        'logo' => is_file(FabPath::logo()) ? FabPath::logo() : public_path(config('fab.paths.logo')),
                        'footer_image' => is_file(FabPath::footer()) ? FabPath::footer() : public_path(config('fab.paths.footer')),
                    ])->setPaper('a4', 'landscape');

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'desempeno-jugadores-' . now()->format('Y-m-d') . '.pdf'
                    );
                }),
        ];
    }
}
