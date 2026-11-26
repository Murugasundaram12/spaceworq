<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderBankAccount extends Model
{
    protected $fillable = [
        'provider_id',
        'account_holder_name',
        'bank_name',
        'account_number_encrypted',
        'account_last4',
        'ifsc_code',
        'upi_id',
        'is_primary',
        'is_verified',
    ];

    protected $hidden = [
        'account_number_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'account_number_encrypted' => 'encrypted',
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function maskedAccountNumber(): string
    {
        return 'XXXX-XXXX-' . $this->account_last4;
    }
}
