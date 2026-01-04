<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membership extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'year',
        'discipline_id',
        'amount',
        'due_date',
        'active',
        'notes',


    ];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'active' => 'boolean',
    ];

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function playerMemberships()
    {
        return $this->hasMany(PlayerMembership::class);
    }
}
