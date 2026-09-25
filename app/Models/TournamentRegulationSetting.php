<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegulationSetting extends Model
{
    protected $fillable = [
        'discipline_id',
        'enabled',
        'minimum_distance_km',
        'check_non_official_distance',
        'block_non_official_against_official_same_state',
        'check_club_category_quota',
        'max_non_official_tournaments_per_category',
        'club_category_period_months',
        'effective_from',
        'effective_until',
        'notes',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'minimum_distance_km' => 'decimal:2',
        'check_non_official_distance' => 'boolean',
        'block_non_official_against_official_same_state' => 'boolean',
        'check_club_category_quota' => 'boolean',
        'max_non_official_tournaments_per_category' => 'integer',
        'club_category_period_months' => 'integer',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }
}
