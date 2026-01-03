<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'document_number',
        'document_type',
        'nationality',
        'birth_date',
        'gender',
        'email',
        'phone',
        'photo_path',
        'club_id',
        'category_id',
        'is_active',
        'is_enabled_to_compete',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'is_enabled_to_compete' => 'boolean',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function registrations()
    {
        return $this->hasMany(TournamentRegistration::class);
    }

    public function disciplines()
    {
        return $this->belongsToMany(Discipline::class, 'player_discipline')
            ->withPivot('enabled_to_compete')
            ->withTimestamps();
    }

    public function memberships()
    {
        return $this->hasMany(PlayerMembership::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payer');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->last_name}, {$this->first_name}";
    }
}


