<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiveQuillasMatch extends Model
{
    protected $fillable = ['phase_id', 'group_id', 'round_number', 'bracket_order', 'label', 'player_one_entry_id', 'player_two_entry_id', 'set_scores', 'player_one_actual_sets', 'player_two_actual_sets', 'player_one_computed_sets', 'player_two_computed_sets', 'player_one_points', 'player_two_points', 'winner_entry_id', 'next_match_id', 'next_match_position', 'loser_next_match_id', 'loser_next_match_position', 'table_number', 'scheduled_at', 'estimated_minutes', 'is_bye', 'status', 'is_public'];
    protected $casts = ['set_scores' => 'array', 'scheduled_at' => 'datetime', 'is_bye' => 'boolean', 'is_public' => 'boolean'];

    public function phase() { return $this->belongsTo(FiveQuillasPhase::class, 'phase_id'); }
    public function group() { return $this->belongsTo(FiveQuillasGroup::class, 'group_id'); }
    public function playerOne() { return $this->belongsTo(FiveQuillasEntry::class, 'player_one_entry_id'); }
    public function playerTwo() { return $this->belongsTo(FiveQuillasEntry::class, 'player_two_entry_id'); }
    public function winner() { return $this->belongsTo(FiveQuillasEntry::class, 'winner_entry_id'); }
    public function nextMatch() { return $this->belongsTo(self::class, 'next_match_id'); }
    public function loserNextMatch() { return $this->belongsTo(self::class, 'loser_next_match_id'); }
}
