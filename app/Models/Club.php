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
        'logo_path',
        'federation_code',
        'is_active',
        'tax_id',
        'contact_person',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    // Pagos institucionales (el club puede pagar afiliaciones de jugadores)
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payer');
    }
}
