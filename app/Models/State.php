<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Testing\Fluent\Concerns\Has;

class State extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'country_id',
        'federation_id',
        'name',
        'latitude',
        'longitude',
        'is_active'
    ];



    public function federation():BelongsTo
    {
        return $this->belongsTo(Federation::class);
    }

    public function cities(): HasMany
    {
        return parent::cities();
    }
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

}
