<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralRanking extends Model
{
    //
    protected $fillable = [
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
        'ptos_4'
    ];
}
