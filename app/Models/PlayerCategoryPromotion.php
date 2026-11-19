<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerCategoryPromotion extends Model
{
    /** Un ascenso pendiente tampoco puede apuntar a categorías de otra disciplina. */
    protected static function booted(): void
    {
        static::saving(fn(self $promotion) => \App\Services\PlayerCategoryDiscipline::validateIds(
            (int)$promotion->player_id, (int)$promotion->new_category_id,
            $promotion->previous_category_id ? (int)$promotion->previous_category_id : null,
        ));
    }
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
