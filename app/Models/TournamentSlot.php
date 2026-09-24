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
        'starts_at',
        'max_players',
        'is_active',

    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'max_players' => 'integer',
        'is_active' => 'boolean',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class, 'tournament_slot_id');
    }

    public function occupiedPlaces(): int
    {
        return $this->registrations()
            ->where('status', '!=', 'denegado')
            ->get(['partner_player_id'])
            ->sum(fn (TournamentRegistration $registration): int => $registration->partner_player_id ? 2 : 1);
    }
}
