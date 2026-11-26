<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PROCESSED = 'PROCESSED';
    public const STATUS_FAILED = 'FAILED';

    protected $fillable = [
        'booking_id',
        'payment_transaction_id',
        'cancellation_record_id',
        'gateway_refund_id',
        'amount',
        'status',
        'reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function cancellationRecord(): BelongsTo
    {
        return $this->belongsTo(CancellationRecord::class);
    }
}
