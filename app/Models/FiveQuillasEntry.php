<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiveQuillasEntry extends Model
{
    protected $fillable = ['competition_id', 'tournament_registration_id', 'registration_participant_id', 'player_id', 'source_slot_id', 'entry_phase_id', 'national_ranking_snapshot', 'seed', 'status'];

    public function competition() { return $this->belongsTo(FiveQuillasCompetition::class, 'competition_id'); }
    public function registration() { return $this->belongsTo(TournamentRegistration::class, 'tournament_registration_id'); }
    public function registrationParticipant() { return $this->belongsTo(TournamentRegistrationParticipant::class, 'registration_participant_id'); }
    public function player() { return $this->belongsTo(Player::class); }
    public function sourceSlot() { return $this->belongsTo(TournamentSlot::class, 'source_slot_id'); }
    public function entryPhase() { return $this->belongsTo(FiveQuillasPhase::class, 'entry_phase_id'); }
}
