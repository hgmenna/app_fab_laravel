<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentContact extends Model
{
    protected $fillable = ['tournament_id', 'name', 'phone', 'sort_order'];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
