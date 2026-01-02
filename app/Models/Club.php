<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Club extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'city_id',
        'address',
        'phone',
        'email_contact',
        'website',
        'logo_path'
    ];

    public function city()
    {
        return $this->belongsTo(\App\Models\City::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }
}
