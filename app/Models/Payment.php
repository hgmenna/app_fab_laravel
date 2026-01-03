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
    ];

    protected $casts = [
        'amount' => 'decimal:2',
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
