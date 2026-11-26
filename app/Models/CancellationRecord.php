<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationRecord extends Model
{
    protected $fillable = [
        'booking_id',
        'cancelled_by_user_id',
        'reason',
        'policy_snapshot',
        'refundable_amount',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'policy_snapshot' => 'array',
            'refundable_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }
}
