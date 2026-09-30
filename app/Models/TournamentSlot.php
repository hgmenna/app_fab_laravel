<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'tournament_id',
        'tournament_modality_id',
        'starts_at',
        'max_players',
        'is_active',

    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'max_players' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $slot): void {
            if ($slot->tournament_modality_id) {
                $slot->tournament_id = TournamentModality::find($slot->tournament_modality_id)?->tournament_id;
            }
        });
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class, 'tournament_slot_id');
    }

    public function tournamentModality()
    {
        return $this->belongsTo(TournamentModality::class);
    }

    public function occupiedPlaces(): int
    {
        return $this->registrations()
            ->where('status', '!=', 'denegado')
            ->count();
    }
}
