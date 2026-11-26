<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReservation extends Model
{
    public const STATUS_HELD = 'HELD';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_RELEASED = 'RELEASED';
    public const STATUS_EXPIRED = 'EXPIRED';

    protected $fillable = [
        'workspace_id',
        'booking_id',
        'reservation_date',
        'start_time',
        'end_time',
        'seats_reserved',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'seats_reserved' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
