<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarkerTournamentLink extends Model
{
    protected $fillable = ['tournament_id', 'external_id', 'club_code', 'club_name', 'token', 'expires_at'];

    protected $hidden = ['token'];

    protected $casts = ['token' => 'encrypted', 'expires_at' => 'datetime'];

    public function assignments(): HasMany
    {
        return $this->hasMany(MarkerMatchAssignment::class);
    }
}
