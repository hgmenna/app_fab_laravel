<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiveQuillasGroup extends Model
{
    protected $fillable = ['phase_id', 'tournament_slot_id', 'name', 'sort_order', 'status', 'is_public'];
    protected $casts = ['is_public' => 'boolean'];

    public function phase() { return $this->belongsTo(FiveQuillasPhase::class, 'phase_id'); }
    public function slot() { return $this->belongsTo(TournamentSlot::class, 'tournament_slot_id'); }
    public function groupEntries() { return $this->hasMany(FiveQuillasGroupEntry::class, 'group_id')->orderBy('position'); }
    public function matches() { return $this->hasMany(FiveQuillasMatch::class, 'group_id'); }
}
