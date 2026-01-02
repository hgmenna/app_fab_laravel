<?php

namespace App\Models;

use Altwaireb\Countries\Models\Country as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'iso_3166_2',
        'iso_3166_3',
        'iso_3166_numeric',
        'phone_code',
        'capital',
        'currency_name',
        'currency_code',
        'currency_symbol',
        'tld',
        'native',
        'region',
        'subregion',
        'timezones',
        'translations',
        'latitude',
        'longitude',
        'emoji',
        'emojiU'
    ];

    public function federations()
    {
        return $this->hasMany(Federation::class);
    }
}
