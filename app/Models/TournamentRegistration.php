<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function instance()
    {
        return $this->belongsTo(TournamentInstance::class, 'tournament_instance_id');
    }

    protected static function booted()
    {
        static::creating(function ($registration) {

            $player = $registration->player;
            $tournament = $registration->tournament;

            if ($player && $tournament) {

                // Obtiene el precio según categoría del jugador
                $price = $tournament->categoryPrices()
                    ->where('category_id', $player->category_id)
                    ->value('price');

                $registration->price = $price ?? 0;
            }
        });
    }

    public function calculatePoints(): float
    {
        $tournament = $this->tournament;
        $type = $tournament?->type;
        $instance = $this->instance;
        $player = $this->player;

        if (! $type || ! $instance) {
            return 0;
        }

        return $instance->points * ($type->score_percentage / 100);
    }
}

