<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Federation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'country_id',
        'mail_contact',
        'website',
        'phone',
        'logo_path'
    ];

    public function country()
    {
        return $this->belongsTo(\App\Models\Country::class);
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);

    }

    public function states(): HasMany
    {
        return $this->hasMany(State::class);
    }

    public function clubs()
    {
        return $this->hasMany(Club::class);
    }
}
