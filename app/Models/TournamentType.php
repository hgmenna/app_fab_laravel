<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TournamentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_official',
        'affects_ranking',
        'assigns_points',
        'score_percentage',
    ];

    protected $casts = [
        'is_official' => 'boolean',
        'affects_ranking' => 'boolean',
        'assigns_points' => 'boolean',
        'score_percentage' => 'decimal:2',
    ];

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }
}
