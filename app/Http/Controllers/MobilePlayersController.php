<?php

namespace App\Http\Controllers;

use App\Auth\GuestUser;
use App\Filament\Resources\Players\PlayerResource;
use App\Models\Category;
use App\Models\Discipline;
use App\Models\Federation;
use App\Models\GeneralRanking;
use App\Models\Player;
use App\Models\TournamentRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class MobilePlayersController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeDirectory($request);
        $filters = $request->validate([
            'buscar' => ['nullable', 'string', 'max:120'],
            'categoria' => ['nullable', 'array', 'max:30'],
            'categoria.*' => ['integer', 'min:1', 'distinct'],
            'disciplina' => ['nullable', 'array', 'max:30'],
            'disciplina.*' => ['integer', 'min:1', 'distinct'],
            'federacion' => ['nullable', 'array', 'max:30'],
            'federacion.*' => ['integer', 'min:1', 'distinct'],
            'estado' => ['nullable', 'in:todos,activos,inactivos'],
            'afiliacion' => ['nullable', 'in:todos,habilitados,inhabilitados'],
        ]);
        $search = trim((string) ($filters['buscar'] ?? ''));
        $state = $filters['estado'] ?? 'todos';
        $affiliation = $filters['afiliacion'] ?? 'todos';
        $played = TournamentRegistration::query()
            ->whereHas('participants', fn (Builder $query) => $query->whereColumn('player_id', 'players.id'))
            ->whereHas('tournament', fn (Builder $query) => $query->whereDate('end_date', '<', today(config('cliente.zona_horaria'))));
        $players = PlayerResource::getEloquentQuery()
            ->select(['id', 'first_name', 'last_name', 'club_id', 'category_id', 'discipline_id', 'is_active', 'is_enabled_to_compete'])
            ->with(['club.city.state.federation', 'category:id,name,code', 'discipline.directFederation'])
            ->addSelect([
                'played_count' => (clone $played)->selectRaw('count(*)'),
                'average_points' => (clone $played)->selectRaw('avg(points)'),
            ])
            ->when(! empty($filters['categoria']), fn (Builder $query) => $query->whereIn('category_id', $filters['categoria']))
            ->when(! empty($filters['disciplina']), fn (Builder $query) => $query->whereIn('discipline_id', $filters['disciplina']))
            ->inAffiliationFederations($filters['federacion'] ?? [])
            ->when($state !== 'todos', fn (Builder $query) => $query->where('is_active', $state === 'activos'))
            ->when($affiliation !== 'todos', fn (Builder $query) => $query->where('is_enabled_to_compete', $affiliation === 'habilitados'))
            ->when($search !== '', function (Builder $query) use ($search): void {
                foreach (preg_split('/[\s,]+/u', $search, -1, PREG_SPLIT_NO_EMPTY) as $term) {
                    $query->where(fn (Builder $names) => $names->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%"));
                }
            })
            ->orderBy('last_name')->orderBy('first_name')->orderBy('id')
            ->simplePaginate(30)->withQueryString();

        return response()->view('mobile-players', [
            'players' => $players,
            'search' => $search,
            'rankings' => GeneralRanking::query()->whereIn('player_id', $players->getCollection()->modelKeys())
                ->get(['player_id', 'RG', 'RC', 'category'])->keyBy('player_id'),
            'filters' => [...$filters, 'estado' => $state, 'afiliacion' => $affiliation],
            'directoryOptions' => [
                'disciplina' => Discipline::query()->orderBy('name')->pluck('name', 'id'),
                'categoria' => Category::query()->orderBy('name')->pluck('name', 'id'),
                'federacion' => Federation::query()->orderBy('name')->pluck('name', 'id'),
            ],
        ])->header('Cache-Control', 'private, no-store');
    }

    public function performance(Request $request, int $player): Response
    {
        $this->authorizeDirectory($request);
        $record = PlayerResource::getEloquentQuery()->findOrFail($player);
        $rows = TournamentRegistration::query()
            ->whereHas('participants', fn (Builder $query) => $query->where('player_id', $record->id))
            ->whereHas('tournament', fn (Builder $query) => $query
                ->whereDate('end_date', '<=', today(config('cliente.zona_horaria')))
                ->whereHas('type', fn (Builder $type) => $type->where('assigns_points', true)))
            ->with(['tournament' => fn ($query) => $query->withCount('registrations')->with(['type', 'discipline']), 'tournamentInstance'])
            ->orderByDesc('id')->simplePaginate(20);

        return response()->view('mobile-player-performance', compact('record', 'rows'))->header('Cache-Control', 'private, no-store');
    }

    private function authorizeDirectory(Request $request): void
    {
        abort_unless(Gate::forUser($request->user() ?? new GuestUser)->allows('viewAny', Player::class), 403);
    }
}
