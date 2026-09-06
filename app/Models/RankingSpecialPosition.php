<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingSpecialPosition extends Model
{
    protected $fillable = [
        'season',
        'champion_player_id',
        'runner_up_player_id',
    ];

    public function champion(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'champion_player_id');
    }

    public function runnerUp(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'runner_up_player_id');
    }
}