<?php

namespace App\Filament\Resources\Players\Pages;

use App\Filament\Resources\Players\PlayerResource;
use App\Jobs\GeneratePlayerPerformancePdf;
use App\Models\Discipline;
use App\Models\Category;
use App\Models\Federation;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use App\Models\TournamentType;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class FilteredPlayerPerformance extends Page
{
    protected static string $resource = PlayerResource::class;

    protected static ?string $title = 'Desempeño de jugadores';

    protected string $view = 'filament.resources.players.pages.filtered-player-performance';

    #[Locked]
    public array $playerIds = [];

    public string $searchPlayer = '';

    public string $disciplineId = '';

    public string $federationId = '';

    public string $categoryId = '';

    public array $typeIds = [];

    public string $fromDate = '';

    public string $untilDate = '';

    public string $minPoints = '';

    public string $maxPoints = '';

    public bool $onlyParticipants = true;

    #[Locked]
    public ?string $pdfExportToken = null;

    #[Locked]
    public ?int $pdfExportStartedAt = null;

    public function mount(string $report): void
    {
        $selection = session("player-performance-reports.{$report}");
        $panelId = Filament::getCurrentPanel()?->getId();

        abort_unless(
            is_array($selection)
                && ($selection['panel_id'] ?? null) === $panelId
                && ($panelId === 'guest' || ($selection['user_id'] ?? null) === Auth::id())
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
        return TournamentType::query()
            ->where('assigns_points', true)
            ->when($this->disciplineId !== '', fn (Builder $query) => $query->where('discipline_id', $this->disciplineId))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function federationOptions(): array
    {
        return Federation::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function categoryOptions(): array
    {
        return Category::query()
            ->when($this->disciplineId !== '', fn (Builder $query) => $query->where('discipline_id', $this->disciplineId))
            ->orderBy('name')->pluck('name', 'id')->all();
    }

    public function updatedDisciplineId(): void
    {
        $this->typeIds = [];
        $this->categoryId = '';
    }

    /**
     * La pantalla y el PDF comparten exactamente la misma selección.
     */
    public function reportData(): array
    {
        $players = Player::query()
            ->whereIn('id', $this->playerIds)
            ->when($this->categoryId !== '', fn (Builder $query) => $query->where('category_id', $this->categoryId))
            ->when($this->federationId !== '', function (Builder $query): void {
                $query->where(fn (Builder $affiliations) => $affiliations
                    ->whereHas('discipline', fn (Builder $discipline) => $discipline
                        ->where('affiliation_mode', 'direct')->where('direct_federation_id', $this->federationId))
                    ->orWhere(fn (Builder $provincial) => $provincial
                        ->whereHas('discipline', fn (Builder $discipline) => $discipline->where('affiliation_mode', 'provincial'))
                        ->whereHas('club.city.state', fn (Builder $state) => $state->where('federation_id', $this->federationId))));
            })
            ->when(trim($this->searchPlayer) !== '', function (Builder $query): void {
                $search = trim($this->searchPlayer);
                $query->where(fn (Builder $names) => $names
                    ->where('last_name', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%"));
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name']);

        $rows = TournamentRegistration::query()
            ->whereHas('participants', fn (Builder $query) => $query->whereIn('player_id', $players->pluck('id')))
            ->whereHas('tournament', function (Builder $tournament): void {
                $tournament
                    ->whereDate('end_date', '<=', today())
                    ->whereHas('type', fn (Builder $type) => $type->where('assigns_points', true));

                if ($this->disciplineId !== '') {
                    $tournament->where('discipline_id', $this->disciplineId);
                }
                if ($this->typeIds !== []) {
                    $tournament->whereIn('tournament_type_id', $this->typeIds);
                }
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->fromDate)) {
                    $tournament->whereDate('end_date', '>=', $this->fromDate);
                }
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->untilDate)) {
                    $tournament->whereDate('end_date', '<=', $this->untilDate);
                }
            })
            ->when($this->minPoints !== '' && is_numeric($this->minPoints),
                fn (Builder $query) => $query->where('points', '>=', (float) $this->minPoints))
            ->when($this->maxPoints !== '' && is_numeric($this->maxPoints),
                fn (Builder $query) => $query->where('points', '<=', (float) $this->maxPoints))
            ->with(['tournament.type', 'tournamentInstance', 'participants'])
            ->orderByDesc(Tournament::query()
                ->select('end_date')
                ->whereColumn('tournaments.id', 'tournament_registrations.tournament_id')
                ->limit(1))
            ->orderByDesc('id')
            ->get();

        $counts = DB::table('tournament_registrations')->whereIn('tournament_id', $rows->pluck('tournament_id')->unique())
            ->groupBy('tournament_id')->selectRaw('tournament_id, COUNT(*) as total')->pluck('total', 'tournament_id');
        $rowsByPlayer = [];
        foreach ($rows as $registration) {
            $registration->setAttribute('participant_count', (int) ($counts[$registration->tournament_id] ?? 0));
            foreach ($registration->participantIds() as $id) {
                $rowsByPlayer[$id][] = $registration;
            }
        }

        $summaries = $players->map(function (Player $player) use ($rowsByPlayer): array {
            $playerRows = collect($rowsByPlayer[(int) $player->id] ?? []);

            return [
                'id' => $player->id,
                'name' => $player->full_name,
                'tournaments' => $playerRows->count(),
                'results' => $playerRows->filter(fn (TournamentRegistration $row): bool => $row->result_code !== null || $row->tournament_instance_id !== null)->count(),
                'points' => $playerRows->sum(fn (TournamentRegistration $row): float => (float) $row->points),
                'rows' => $playerRows,
            ];
        })->when(
            $this->onlyParticipants,
            fn ($summaries) => $summaries->filter(fn (array $player): bool => $player['tournaments'] > 0)->values()
        )->sortByDesc('points')->values();

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
                ->disabled(fn (): bool => $this->pdfExportToken !== null)
                ->action(fn () => $this->startPdfExport()),
        ];
    }

    public function startPdfExport(): void
    {
        if ($this->pdfExportToken !== null) return;
        $token = (string) Str::uuid();
        $snapshot = collect(['playerIds', 'searchPlayer', 'disciplineId', 'federationId', 'categoryId',
            'typeIds', 'fromDate', 'untilDate', 'minPoints', 'maxPoints', 'onlyParticipants'])
            ->mapWithKeys(fn ($key) => [$key => $this->$key])->all();
        session()->put('player-performance-pdf.'.$token, ['user_id' => Auth::id(), 'expires_at' => now()->addHour()->timestamp]);
        Cache::put('player-performance-pdf:'.$token, 'pending', now()->addHour());
        GeneratePlayerPerformancePdf::dispatchAfterResponse($token, $snapshot);
        $this->pdfExportToken = $token;
        $this->pdfExportStartedAt = now()->timestamp;
    }

    public function checkPdfExport(): void
    {
        if (! $this->pdfExportToken) { $this->skipRender(); return; }
        $state = Cache::get('player-performance-pdf:'.$this->pdfExportToken);
        if ($state === 'ready') {
            $this->dispatch('player-performance-pdf-ready', url: route('players.performance-pdf', ['token' => $this->pdfExportToken]));
            $this->pdfExportToken = null;
        } elseif ($state === 'failed' || now()->timestamp - (int) $this->pdfExportStartedAt > 600) {
            \Filament\Notifications\Notification::make()->danger()->title('No se pudo generar el PDF')
                ->body('Intentá nuevamente o reducí el período del informe.')->persistent()->send();
            $this->pdfExportToken = null;
        } else {
            $this->skipRender();
        }
    }
}
