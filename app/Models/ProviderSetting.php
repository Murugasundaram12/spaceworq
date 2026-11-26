<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderSetting extends Model
{
    protected $fillable = [
        'provider_id',
        'auto_accept_instant_bookings',
        'notification_email',
        'gst_registered',
        'default_tax_rate',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'auto_accept_instant_bookings' => 'boolean',
            'gst_registered' => 'boolean',
            'default_tax_rate' => 'decimal:2',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
