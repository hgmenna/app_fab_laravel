<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentRegulationAudit extends Model
{
    protected $fillable = [
        'tournament_id',
        'user_id',
        'operation',
        'result',
        'overridden',
        'override_reason',
        'tournament_snapshot',
        'conflicts',
        'technical_details',
    ];

    protected $casts = [
        'overridden' => 'boolean',
        'tournament_snapshot' => 'array',
        'conflicts' => 'array',
        'technical_details' => 'array',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
