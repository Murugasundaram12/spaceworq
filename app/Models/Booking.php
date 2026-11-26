<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const STATUS_UPCOMING = 'Upcoming';
    public const STATUS_ACTIVE = 'Active';
    public const STATUS_IN_PROGRESS = 'In Progress';
    public const STATUS_REVIEW = 'Review';
    public const STATUS_COMPLETED = 'Completed';
    public const STATUS_CANCELLED = 'Cancelled';

    public const PROVIDER_STATUSES = [
        self::STATUS_UPCOMING,
        self::STATUS_ACTIVE,
        self::STATUS_IN_PROGRESS,
        self::STATUS_REVIEW,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const PAYMENT_UNPAID = 'UNPAID';
    public const PAYMENT_PENDING = 'PENDING';
    public const PAYMENT_PAID = 'PAID';
    public const PAYMENT_FAILED = 'FAILED';
    public const PAYMENT_REFUNDED = 'REFUNDED';

    protected $fillable = [
        'booking_reference',
        'idempotency_key',
        'workspace_id',
        'provider_id',
        'customer_user_id',
        'assigned_provider_user_id',
        'booking_type',
        'booking_date',
        'slot',
        'start_time',
        'end_time',
        'seat_count',
        'unit_price',
        'subtotal_amount',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'taxable_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'currency',
        'status',
        'payment_status',
        'hold_expires_at',
        'contact_name',
        'contact_email',
        'contact_phone',
        'remarks',
        'checkin_token',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'seat_count' => 'integer',
            'unit_price' => 'decimal:2',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'hold_expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(ProviderUser::class, 'assigned_provider_user_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function cancellationRecord(): HasOne
    {
        return $this->hasOne(CancellationRecord::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
