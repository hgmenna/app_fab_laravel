<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankTransferOrder extends Model
{
    protected $fillable = [
        'code', 'user_id', 'club_id', 'discipline_id', 'purpose', 'amount', 'status',
        'expires_at', 'receipt_path', 'transferred_at', 'transferred_amount', 'origin_bank',
        'origin_holder', 'transaction_number', 'submitter_notes', 'review_notes',
        'submitted_at', 'reviewed_by', 'reviewed_at',
        'beneficiary_type', 'beneficiary_name', 'destination_account_holder',
        'destination_tax_id', 'destination_bank_name', 'destination_account_type',
        'destination_account_number', 'destination_cbu', 'destination_alias',
        'destination_treasury_email', 'destination_instructions',
        'payer_type', 'payer_id', 'payer_name',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2', 'transferred_amount' => 'decimal:2',
            'expires_at' => 'date', 'transferred_at' => 'date',
            'submitted_at' => 'datetime', 'reviewed_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BankTransferOrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(Discipline::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
