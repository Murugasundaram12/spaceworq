<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    public const STATUS_CREATED = 'CREATED';
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_CAPTURED = 'CAPTURED';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_REFUNDED = 'REFUNDED';

    protected $fillable = [
        'booking_id',
        'customer_user_id',
        'gateway',
        'idempotency_key',
        'gateway_order_id',
        'gateway_payment_id',
        'amount',
        'currency',
        'status',
        'failure_reason',
        'verified_at',
        'gateway_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'verified_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
