<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'latitude',
        'longitude',
        'is_active',
        'postal_code'
    ];

    public function clubs()
    {
        return $this->hasMany(Club::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
