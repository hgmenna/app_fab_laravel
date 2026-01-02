<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TournamentInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'points',
        'description',
        'instance',
    ];
}
