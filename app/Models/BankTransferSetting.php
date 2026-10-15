<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransferSetting extends Model
{
    protected $fillable = [
        'account_holder', 'tax_id', 'bank_name', 'account_type', 'account_number',
        'cbu', 'alias', 'expires_in_days', 'treasury_email', 'instructions', 'is_active',
    ];

    protected function casts(): array
    {
        return ['expires_in_days' => 'integer', 'is_active' => 'boolean'];
    }

    public static function active(): ?self
    {
        return self::query()->where('is_active', true)->latest('id')->first();
    }
}
