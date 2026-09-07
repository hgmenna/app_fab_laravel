<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerCategoryHistory extends Model
{
    protected $fillable = [
        'player_id',
        'season',
        'category_id',
        'change_type',
        'previous_category_id',
        'source',
        'tournament_id',
        'ranking_id',
        'effective_date',
        'applied_at',
        'reason',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'applied_at' => 'datetime',
    ];

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function previousCategory()
    {
        return $this->belongsTo(Category::class, 'previous_category_id');
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