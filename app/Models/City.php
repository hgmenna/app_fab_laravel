<?php

namespace App\Models;

use Altwaireb\Countries\Models\City as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Testing\Fluent\Concerns\Has;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'latitude',
        'longitude',
        'is_active'
    ];

    public function clubs()
    {
        return $this->hasMany(Club::class);
    }
}
