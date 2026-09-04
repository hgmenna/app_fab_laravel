<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingHistory extends Model
{
    protected $fillable = [

        'season',
        'RG',
        'RC',
        'category',
        'last_name',
        'first_name',
        'club',
        'fed', 
        'total_puntos',
        'pos_1',
        'ptos_1',
        'pos_2',
        'ptos_2',
        'pos_3',
        'ptos_3',
        'pos_4',
        'ptos_4',
        'total_penalties',
        'player_id',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
