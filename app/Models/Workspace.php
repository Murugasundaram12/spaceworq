<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_IN_REVIEW = 'In Review';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    public const APPROVAL_PENDING = 'PENDING';
    public const APPROVAL_APPROVED = 'APPROVED';
    public const APPROVAL_REJECTED = 'REJECTED';

    protected $fillable = [
        'provider_id',
        'code',
        'slug',
        'name',
        'generated_post_title',
        'category_id',
        'subcategory_id',
        'city_id',
        'locality_id',
        'country',
        'address_line',
        'landmark',
        'pincode',
        'place_id',
        'latitude',
        'longitude',
        'description_html',
        'total_floors',
        'total_seats',
        'available_seats',
        'is_upcoming',
        'tentative_availability_date',
        'gst_enabled',
        'tax_rate',
        'rating_avg',
        'reviews_count',
        'status',
        'approval_status',
        'is_published',
        'is_instant_bookable',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'total_floors' => 'integer',
            'total_seats' => 'integer',
            'available_seats' => 'integer',
            'is_upcoming' => 'boolean',
            'tentative_availability_date' => 'date',
            'gst_enabled' => 'boolean',
            'tax_rate' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'reviews_count' => 'integer',
            'is_published' => 'boolean',
            'is_instant_bookable' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where('status', self::STATUS_ACTIVE)
            ->where('approval_status', self::APPROVAL_APPROVED)
            ->whereHas('provider', function (Builder $providerQuery) {
                $providerQuery->where('status', Provider::STATUS_ACTIVE);
            });
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkspaceCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(WorkspaceSubcategory::class, 'subcategory_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function listingConfigs(): HasMany
    {
        return $this->hasMany(WorkspaceListingConfig::class);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkspaceWorkingHour::class)->orderBy('day_of_week');
    }

    public function images(): HasMany
    {
        return $this->hasMany(WorkspaceImage::class)->orderBy('sort_order');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'workspace_amenities');
    }

    public function floors(): HasMany
    {
        return $this->hasMany(WorkspaceFloor::class)->orderBy('floor_number');
    }

    public function templateMappings(): HasMany
    {
        return $this->hasMany(FloorTemplateMapping::class);
    }

    public function approvalHistories(): HasMany
    {
        return $this->hasMany(WorkspaceApprovalHistory::class)->latest();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(WorkspaceReview::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reserveRequests(): HasMany
    {
        return $this->hasMany(ReserveRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
