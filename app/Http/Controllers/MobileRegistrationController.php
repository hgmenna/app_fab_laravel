<?php

namespace App\Http\Controllers;

use App\Auth\GuestUser;
use App\Filament\Resources\BankTransferOrders\BankTransferOrderResource;
use App\Filament\Resources\TournamentRegistrations\Schemas\TournamentRegistrationForm;
use App\Models\Tournament;
use App\Models\TournamentRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class MobileRegistrationController extends Controller
{
    public function show(Request $request, Tournament $tournament): Response
    {
        $this->authorizeRegistration($request, $tournament);

        return response()->view('mobile-registration', [
            'tournament' => $tournament,
            'modalities' => $tournament->tournamentModalities()->with('modality')->get(),
            'confirmation' => $request->session()->get('mobile_registration'),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function options(Request $request, Tournament $tournament): JsonResponse
    {
        $this->authorizeRegistration($request, $tournament);
        abort_unless($tournament->isRegistrationOpen(), 409, 'La inscripción está cerrada.');
        $data = $request->validate(['modality' => ['required', 'integer', 'min:1']]);
        $modality = $tournament->tournamentModalities()->with('modality')->findOrFail($data['modality']);

        return response()->json([
            'required' => (int) $modality->modality->players_per_registration,
            'players' => collect(TournamentRegistrationForm::eligiblePlayers($modality->id))
                ->map(fn ($name, $id) => ['id' => $id, 'name' => $name])->values(),
            'slots' => $tournament->slots()->where('tournament_modality_id', $modality->id)
                ->where('is_active', true)->orderBy('starts_at')
                ->withCount(['registrations as occupied_places' => fn ($query) => $query->where('status', '!=', 'denegado')])
                ->get()->map(fn ($slot) => [
                    'id' => $slot->id,
                    'name' => $slot->name,
                    'starts_at' => $slot->starts_at?->format('d/m/Y H:i'),
                    'capacity' => $slot->max_players,
                    'remaining' => $slot->max_players === null ? null : max(0, $slot->max_players - $slot->occupied_places),
                ])->values(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request, Tournament $tournament): RedirectResponse
    {
        $data = $request->validate([
            'tournament_modality_id' => ['required', 'integer', 'min:1'],
            'player_ids' => ['required', 'array', 'max:20'],
            'player_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'tournament_slot_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $registration = DB::transaction(function () use ($request, $tournament, $data) {
            $locked = Tournament::query()->lockForUpdate()->findOrFail($tournament->id);
            $this->authorizeRegistration($request, $locked);
            if (! $locked->isRegistrationOpen()) {
                throw ValidationException::withMessages(['tournament_modality_id' => 'La inscripción está cerrada.']);
            }
            if (! empty($data['tournament_slot_id']) && ! $locked->slots()
                ->where('tournament_modality_id', $data['tournament_modality_id'])
                ->where('is_active', true)->whereKey($data['tournament_slot_id'])->exists()) {
                throw ValidationException::withMessages(['tournament_slot_id' => 'Elegí un horario habilitado para esta modalidad.']);
            }

            return $locked->registrations()->create([...$data, 'status' => 'pendiente']);
        });
        $order = $registration->bankTransferOrderItems()->with('order')->latest('id')->first()?->order;

        return redirect()->route('mobile-registration.show', $tournament)->with('mobile_registration', [
            'tournament_id' => $tournament->id,
            'payment_url' => $order ? BankTransferOrderResource::getUrl('edit', ['record' => $order], panel: $request->user() ? 'admin' : 'guest') : null,
        ]);
    }

    private function authorizeRegistration(Request $request, Tournament $tournament): void
    {
        $user = $request->user() ?? new GuestUser;
        abort_unless(Gate::forUser($user)->allows('create', TournamentRegistration::class)
            && $user->canAccessClubInDiscipline('Create:TournamentRegistration', $tournament->discipline_id, $tournament->organizer_club_id), 403);
    }
}
