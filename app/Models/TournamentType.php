<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'discipline_id',
        'name',
        'code',
        'participation_mode',
        'has_handicap',
        'is_official',
        'affects_ranking',
        'assigns_points',
        'scoring_method',
        'scoring_rules',
        'is_active',
    ];

    protected $casts = [
        'is_official' => 'boolean',
        'has_handicap' => 'boolean',
        'affects_ranking' => 'boolean',
        'assigns_points' => 'boolean',
        'scoring_rules' => 'array',
        'is_active' => 'boolean',
    ];

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }
}
