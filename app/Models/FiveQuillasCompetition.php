<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Raíz deportiva de un torneo de cinco quillas; agrupa fases, entradas y configuración pública.
 */
class FiveQuillasCompetition extends Model
{
    protected $fillable = ['tournament_id', 'public_slug', 'status', 'is_public', 'settings'];
    protected $casts = ['is_public' => 'boolean', 'settings' => 'array'];

    public function tournament() { return $this->belongsTo(Tournament::class); }
    public function phases() { return $this->hasMany(FiveQuillasPhase::class, 'competition_id')->orderBy('sort_order'); }
    public function entries() { return $this->hasMany(FiveQuillasEntry::class, 'competition_id'); }
}
