<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_membership_id',
        'payer_type',
        'payer_id',
        'amount',
        'method',
        'external_reference',
        'status',
        'manual_method',
        'notes',
        'recorded_by',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function membership()
    {
        return $this->belongsTo(PlayerMembership::class, 'player_membership_id');
    }

    public function payer()
    {
        return $this->morphTo();
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Lógica de negocio
    |--------------------------------------------------------------------------
    */

    public function approve(): void
    {
        $this->status = 'approved';
        $this->save();

        // Actualizar la afiliación del jugador
        $membership = $this->membership;

        $membership->amount_paid += $this->amount;
        $membership->refreshStatus();
    }

    public function fail(): void
    {
        $this->status = 'failed';
        $this->save();
    }
}
