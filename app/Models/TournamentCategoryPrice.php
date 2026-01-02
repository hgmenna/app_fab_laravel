<?php

namespace App\Models;

use App\Models\Category;
use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentCategoryPrice extends Model
{
    protected $table = 'tournament_category_price';

    protected $fillable = [
        'tournament_id',
        'category_id',
        'price',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}


