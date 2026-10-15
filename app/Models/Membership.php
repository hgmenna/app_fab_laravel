<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

class Membership extends Model
{
    use HasFactory;

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

    protected static function booted(): void
    {
        static::saving(function (Membership $membership): void {
            $duplicate = static::query()
                ->where('year', $membership->year)
                ->where('discipline_id', $membership->discipline_id)
                ->when($membership->exists, fn ($query) => $query->whereKeyNot($membership->getKey()))
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'year' => 'Ya existe una configuración de membresía para esta disciplina y año.',
                ]);
            }
        });
    }

    public function discipline()
    {
        return $this->belongsTo(Discipline::class);
    }

    public function playerMemberships()
    {
        return $this->hasMany(PlayerMembership::class);
    }
}
