<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'PENDING_VERIFICATION';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_REJECTED = 'REJECTED';

    public const KYC_NOT_SUBMITTED = 'NOT_SUBMITTED';
    public const KYC_PENDING = 'PENDING';
    public const KYC_VERIFIED = 'VERIFIED';
    public const KYC_REJECTED = 'REJECTED';

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending Review',
        self::STATUS_ACTIVE => 'Approved',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_SUSPENDED => 'Suspended',
    ];

    public const KYC_STATUS_LABELS = [
        self::KYC_NOT_SUBMITTED => 'Not Submitted',
        self::KYC_PENDING => 'Under Review',
        self::KYC_VERIFIED => 'Verified',
        self::KYC_REJECTED => 'Rejected',
    ];

    protected $fillable = [
        'owner_user_id',
        'company_name',
        'slug',
        'brand_name',
        'business_email',
        'business_phone',
        'gstin',
        'pan',
        'city_id',
        'address',
        'submitted_at',
        'status',
        'kyc_status',
        'kyc_notes',
        'status_reason',
        'status_updated_at',
        'status_updated_by',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'status_updated_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? (string) $this->status;
    }

    public function kycStatusLabel(): string
    {
        return self::KYC_STATUS_LABELS[$this->kyc_status] ?? (string) $this->kyc_status;
    }

    public function isVerifiedAndActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->kyc_status === self::KYC_VERIFIED;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function statusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_updated_by');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function providerUsers(): HasMany
    {
        return $this->hasMany(ProviderUser::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(ProviderRole::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProviderDocument::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(ProviderBankAccount::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(ProviderSetting::class);
    }

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reserveRequests(): HasMany
    {
        return $this->hasMany(ReserveRequest::class);
    }

    public function layoutTemplates(): HasMany
    {
        return $this->hasMany(LayoutTemplate::class);
    }
}
