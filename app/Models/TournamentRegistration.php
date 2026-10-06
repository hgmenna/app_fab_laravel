<?php

namespace App\Models;

use App\Services\TournamentScoringService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class TournamentRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'tournament_modality_id',
        'tournament_slot_id',
        'player_id',
        'partner_player_id',
        'player_ids',
        'status',
        'price',
        'payment_status',
        'checked_in',
        'source',
        'notes',
        'tournament_instance_id',
        'result_code',
        'result_description',
        'result_instance_value',
        'payment_file',
        'points',
        'penalty_points',
    ];

    protected $casts = [
        'tournament_slot_id' => 'integer',
        'price' => 'decimal:2',
        'checked_in' => 'boolean',
        'result_instance_value' => 'integer',
        'player_ids' => 'array',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function tournamentModality()
    {
        return $this->belongsTo(TournamentModality::class);
    }

    public function participants()
    {
        return $this->hasMany(TournamentRegistrationParticipant::class)->orderBy('position');
    }

    public function participantIds(): array
    {
        if ($this->relationLoaded('participants')) {
            $ids = $this->participants->pluck('player_id')->map(fn ($id) => (int) $id)->all();

            if ($ids !== []) {
                return $ids;
            }
        } elseif ($this->exists) {
            $ids = $this->participants()->pluck('player_id')->map(fn ($id) => (int) $id)->all();

            if ($ids !== []) {
                return $ids;
            }
        }

        return collect($this->player_ids ?? [$this->player_id, $this->partner_player_id])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function getParticipantNamesAttribute(): string
    {
        $participants = $this->relationLoaded('participants')
            ? $this->participants
            : ($this->exists ? $this->participants()->with('player')->get() : collect());

        $names = $participants
            ->map(fn (TournamentRegistrationParticipant $participant): ?string => $participant->player?->full_name)
            ->filter()
            ->implode(' / ');

        if ($names !== '') {
            return $names;
        }

        return collect([$this->player, $this->partner])
            ->filter()
            ->map(fn (Player $player): string => $player->full_name)
            ->implode(' / ');
    }

    public function slot()
    {
        return $this->belongsTo(TournamentSlot::class, 'tournament_slot_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function partner()
    {
        return $this->belongsTo(Player::class, 'partner_player_id');
    }

    public function tournamentInstance()
    {
        return $this->belongsTo(TournamentInstance::class, 'tournament_instance_id');
    }

    protected static function booted()
    {
        static::saving(function (TournamentRegistration $registration) {
            if ($registration->exists && ! $registration->isDirty([
                'tournament_id',
                'tournament_modality_id',
                'player_ids',
                'player_id',
                'partner_player_id',
            ])) {
                return;
            }

            $playerIds = collect($registration->player_ids ?? [$registration->player_id, $registration->partner_player_id])
                ->filter()->map(fn ($id) => (int) $id)->unique()->values();
            $registration->player_ids = $playerIds->all();
            $registration->player_id = $playerIds->get(0);
            $registration->partner_player_id = $playerIds->get(1);

            $tournament = Tournament::query()
                ->with('type')
                ->find($registration->tournament_id);

            if (! $tournament) {
                throw ValidationException::withMessages([
                    'tournament_id' => 'El torneo seleccionado no existe.',
                ]);
            }

            $configuration = TournamentModality::query()->with('modality')
                ->where('tournament_id', $tournament->id)->find($registration->tournament_modality_id);
            if (! $configuration) {
                throw ValidationException::withMessages([
                    'tournament_modality_id' => 'Seleccioná una modalidad habilitada para el torneo.',
                ]);
            }
            $requiredPlayers = (int) $configuration->modality->players_per_registration;
            if ($playerIds->count() !== $requiredPlayers) {
                throw ValidationException::withMessages([
                    'player_ids' => "La modalidad requiere exactamente {$requiredPlayers} integrante(s).",
                ]);
            }

            if ($registration->tournament_slot_id) {
                $slot = TournamentSlot::query()
                    ->where('tournament_id', $tournament->id)
                    ->where('tournament_modality_id', $configuration->id)
                    ->find($registration->tournament_slot_id);

                if (! $slot) {
                    throw ValidationException::withMessages([
                        'tournament_slot_id' => 'El horario seleccionado no pertenece a la modalidad elegida.',
                    ]);
                }

                if ($slot->max_players !== null) {
                    $occupiedPlaces = $slot->registrations()
                        ->where('status', '!=', 'denegado')
                        ->when(
                            $registration->exists,
                            fn ($query) => $query->whereKeyNot($registration->getKey())
                        )
                        ->count();

                    $requiredPlaces = $registration->status === 'denegado'
                        ? 0
                        : 1;

                    if ($occupiedPlaces + $requiredPlaces > $slot->max_players) {
                        throw ValidationException::withMessages([
                            'tournament_slot_id' => 'El horario no tiene lugares suficientes para esta inscripción.',
                        ]);
                    }
                }
            }

            $players = $playerIds->mapWithKeys(fn (int $id, int $index) => ["player_ids.{$index}" => $id]);

            foreach ($players as $field => $playerId) {
                $player = Player::find($playerId);

                if (! $player) {
                    throw ValidationException::withMessages([
                        $field => 'El jugador seleccionado no existe.',
                    ]);
                }

                /*
                * 1) El jugador debe estar habilitado para competir.
                */
                if (! $player->is_enabled_to_compete) {
                    throw ValidationException::withMessages([
                        $field => 'Este jugador no está habilitado para competir.',
                    ]);
                }

                /*
                * 2) Categorías habilitadas para este torneo.
                */
                $enabledCategoryIds = collect($configuration->categories ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->values();

                if ($enabledCategoryIds->isEmpty()) {
                    throw ValidationException::withMessages([
                        'player_ids' => 'La modalidad no tiene categorías habilitadas.',
                    ]);
                }

                $enabledCategories = Category::query()
                    ->whereIn('id', $enabledCategoryIds)
                    ->get(['id', 'code']);

                /*
                * Master y Nacional se verifican contra el Ranking General vigente.
                */
                $rankingCodes = $enabledCategories
                    ->whereIn('code', ['M', 'N'])
                    ->pluck('code')
                    ->values();

                /*
                * Las demás categorías se verifican contra la categoría
                * permanente del jugador.
                */
                $permanentCategoryIds = $enabledCategories
                    ->whereNotIn('code', ['M', 'N'])
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->values();

                $validByPermanentCategory = $permanentCategoryIds
                    ->contains((int) $player->category_id);

                $validByRanking = false;

                if ($rankingCodes->isNotEmpty()) {
                    $validByRanking = GeneralRanking::query()
                        ->where('player_id', $player->id)
                        ->whereIn('category', $rankingCodes)
                        ->exists();
                }

                if (! $validByPermanentCategory && ! $validByRanking) {
                    throw ValidationException::withMessages([
                        $field => 'El jugador no pertenece a una categoría habilitada para este torneo.',
                    ]);
                }

                /*
                * 3) Evitar que un jugador se inscriba dos veces
                * en el mismo torneo.
                */
                $exists = self::query()
                    ->where('tournament_id', $registration->tournament_id)
                    ->where('tournament_modality_id', $registration->tournament_modality_id)
                    ->whereHas('participants', fn ($query) => $query->where('player_id', $playerId))
                    ->when(
                        $registration->exists,
                        fn ($query) => $query->whereKeyNot($registration->getKey())
                    )
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        $field => 'Este jugador ya está inscripto en esta modalidad del torneo.',
                    ]);
                }
            }
        });

        static::saved(function (TournamentRegistration $registration): void {
            if (! $registration->wasRecentlyCreated && ! $registration->wasChanged([
                'tournament_id',
                'tournament_modality_id',
                'player_ids',
                'player_id',
                'partner_player_id',
            ])) {
                return;
            }

            $registration->participants()->delete();
            foreach ($registration->player_ids ?? [] as $index => $playerId) {
                $registration->participants()->create(['player_id' => $playerId, 'position' => $index + 1]);
            }
        });

        static::deleting(function (TournamentRegistration $registration): void {
            if (\Illuminate\Support\Facades\Schema::hasTable('three_cushion_stage_results')) {
                $registration->threeCushionStageResult?->delete();
            }
        });
    }

    public function calculatePoints(): float
    {
        $this->loadMissing([
            'tournament.type',
            'tournamentInstance',
        ]);

        $tournament = $this->tournament;
        $instance = $this->tournamentInstance;

        if (! $tournament || ! $instance) {
            return 0.0;
        }

        $rule = app(
            TournamentScoringService::class
        )->findRuleByTournamentInstance(
            $tournament,
            $instance
        );

        if (
            ! $rule
            || ! array_key_exists('points', $rule)
        ) {
            throw ValidationException::withMessages([
                'tournament_instance_id' => 'La posición seleccionada no tiene puntos configurados para este tipo de torneo.',
            ]);
        }

        return (float) $rule['points'];
    }

    public function threeCushionStageResult()
    {
        return $this->hasOne(ThreeCushionStageResult::class, 'tournament_registration_id');
    }
}
