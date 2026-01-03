<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlayerMembership extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'membership_id',
        'club_id',
        'amount_due',
        'amount_paid',
        'status',
        'enabled_to_compete',
        'paid_at',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'enabled_to_compete' => 'boolean',
        'paid_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica de negocio
    |--------------------------------------------------------------------------
    */

    public function refreshStatus(): void
    {
        if ($this->amount_paid >= $this->amount_due) {
            $this->status = 'paid';
            $this->enabled_to_compete = true;
            $this->paid_at = now();
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }

        $this->save();
    }
}
