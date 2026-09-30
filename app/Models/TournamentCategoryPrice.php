<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TournamentCategoryPrice extends Model
{
    protected $table = 'tournament_category_price';

    protected $fillable = [
        'tournament_id',
        'tournament_modality_id',
        'category_id',
        'price',
    ];

    protected $casts = [
        'category_id' => 'integer',
        'price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $price): void {
            if ($price->tournament_modality_id) {
                $price->tournament_id = TournamentModality::find($price->tournament_modality_id)?->tournament_id;
            }
        });
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tournamentModality()
    {
        return $this->belongsTo(TournamentModality::class);
    }
}
