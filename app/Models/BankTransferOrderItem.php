<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BankTransferOrderItem extends Model
{
    protected $fillable = ['payable_type', 'payable_id', 'description', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(BankTransferOrder::class, 'bank_transfer_order_id');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
