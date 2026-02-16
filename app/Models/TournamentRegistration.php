<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class TournamentRegistration extends Model
{
    use HasFactory, SoftDeletes;

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
        static::creating(function ($registration) {
            $exists = self::where('tournament_id', $registration->tournament_id)
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

