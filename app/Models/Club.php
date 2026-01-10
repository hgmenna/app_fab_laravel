<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Club extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'short_name',
        'logo_path',
        'website',
        'mail_contact',
        'contact_person',
        'notes',
        'phone',
        'address',
        'is_active',
        'tax_id',
        'city_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function booted()
    {
        static::addGlobalScope('ordered', function (Builder $query) {
            $query->orderBy('name');
        });
    }

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

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }


}
