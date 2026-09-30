<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentRegistrationParticipant extends Model
{
    protected $fillable = ['tournament_registration_id', 'player_id', 'position'];

    public function registration()
    {
        return $this->belongsTo(TournamentRegistration::class, 'tournament_registration_id');
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
