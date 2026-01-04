<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerCategoryHistory extends Model
{
   protected $table = 'player_category_history';

    protected $fillable = [
        'player_id',
        'category_id',
        'source',
        'tournament_id',
        'ranking_id',
        'notes',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function ranking()
    {
        return $this->belongsTo(Ranking5Quillas::class, 'ranking_id');
    }

}
