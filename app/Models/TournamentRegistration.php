<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

class TournamentRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'tournament_slot_id',
        'player_id',
        'status',
        'price',
        'payment_status',
        'checked_in',
        'source',
        'notes',
        'tournament_instance_id',
        'payment_file',
        'points',
        'penalty_points',       
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'checked_in' => 'boolean',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function slot()
    {
        return $this->belongsTo(TournamentSlot::class, 'tournament_slot_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function tournamentInstance()
    {
        return $this->belongsTo(TournamentInstance::class, 'tournament_instance_id');
    }

    protected static function booted()
    {
        static::creating(function (TournamentRegistration $registration) {
            $tournament = Tournament::find($registration->tournament_id);
            $player = Player::find($registration->player_id);

            if (! $tournament) {
                throw ValidationException::withMessages([
                    'tournament_id' => 'El torneo seleccionado no existe.',
                ]);
            }

            if (! $player) {
                throw ValidationException::withMessages([
                    'player_id' => 'El jugador seleccionado no existe.',
                ]);
            }

            /*
            * 1) El jugador debe estar habilitado para competir.
            */
            if (! $player->is_enabled_to_compete) {
                throw ValidationException::withMessages([
                    'player_id' => 'Este jugador no está habilitado para competir.',
                ]);
            }

            /*
            * 2) Categorías habilitadas para este torneo.
            */
            $enabledCategoryIds = collect($tournament->categories ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            if ($enabledCategoryIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'player_id' => 'El torneo no tiene categorías habilitadas.',
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
                    'player_id' => 'El jugador no pertenece a una categoría habilitada para este torneo.',
                ]);
            }

            /*
            * 3) Evitar que un jugador se inscriba dos veces
            * en el mismo torneo.
            */
            $exists = self::query()
                ->where('tournament_id', $registration->tournament_id)
                ->where('player_id', $registration->player_id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'player_id' => 'Este jugador ya está inscripto en este torneo.',
                ]);
            }
        });
    }


    public function calculatePoints(): float
    {
        $tournament = $this->tournament;
        $type = $tournament?->type;
        $instance = $this->tournamentInstance;
        $player = $this->player;

        if (! $type || ! $instance) {
            return 0;
        }

        return $instance->points * ($type->score_percentage / 100);
    }

    

}

