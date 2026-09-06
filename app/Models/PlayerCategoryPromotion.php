<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerCategoryPromotion extends Model
{
    protected $fillable = [
        'player_id',
        'season',
        'previous_category_id',
        'new_category_id',
        'promotion_type',
        'final_rg',
        'final_rc',
        'effective_date',
        'applied_at',
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

    public function previousCategory()
    {
        return $this->belongsTo(Category::class, 'previous_category_id');
    }

    public function newCategory()
    {
        return $this->belongsTo(Category::class, 'new_category_id');
    }
}