<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Posición de una entrada dentro de una zona junto con sus estadísticas recalculables.
 */
class FiveQuillasGroupEntry extends Model
{
    protected $fillable = ['group_id', 'entry_id', 'position', 'match_wins', 'sets_difference', 'points_for', 'points_against', 'points_average', 'final_place'];
    protected $casts = ['points_average' => 'decimal:3'];

    public function group() { return $this->belongsTo(FiveQuillasGroup::class, 'group_id'); }
    public function entry() { return $this->belongsTo(FiveQuillasEntry::class, 'entry_id'); }
}
