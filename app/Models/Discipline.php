<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Discipline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'description',
        'scoring_rules',
        'active',
    ];

    protected $casts = [
        'scoring_rules' => 'array',
        'active' => 'boolean',
    ];

    public function players()
    {
        return $this->belongsToMany(Player::class, 'player_discipline')
            ->withPivot('enabled_to_compete')
            ->withTimestamps();
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }

    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }
}
