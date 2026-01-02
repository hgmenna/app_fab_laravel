<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TournamentSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'starts_at',
        'max_players',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }
}
