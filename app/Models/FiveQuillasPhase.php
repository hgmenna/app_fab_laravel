<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiveQuillasPhase extends Model
{
    protected $fillable = ['competition_id', 'code', 'name', 'phase_type', 'sort_order', 'sets_to_play', 'points_per_set', 'seeding_method', 'split_by_slot', 'is_enabled', 'status', 'is_public', 'settings'];
    protected $casts = ['split_by_slot' => 'boolean', 'is_enabled' => 'boolean', 'is_public' => 'boolean', 'settings' => 'array'];

    public function competition() { return $this->belongsTo(FiveQuillasCompetition::class, 'competition_id'); }
    public function groups() { return $this->hasMany(FiveQuillasGroup::class, 'phase_id')->orderBy('sort_order'); }
    public function matches() { return $this->hasMany(FiveQuillasMatch::class, 'phase_id')->orderBy('round_number')->orderBy('bracket_order'); }
}
