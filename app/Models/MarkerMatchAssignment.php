<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarkerMatchAssignment extends Model
{
    protected $fillable = ['five_quillas_match_id', 'external_id', 'table_number', 'revision', 'points_per_set', 'sets_to_win', 'status'];

    public function match(): BelongsTo
    {
        return $this->belongsTo(FiveQuillasMatch::class, 'five_quillas_match_id');
    }
}
