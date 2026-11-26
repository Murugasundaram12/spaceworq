<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    public const TYPE_CUSTOMER = 'customer';
    public const TYPE_PROVIDER = 'provider';
    public const TYPE_SUPER_ADMIN = 'super_admin';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    public const STATUS_PENDING_ACTIVATION = 'PENDING_ACTIVATION';
    public const STATUS_SUSPENDED = 'SUSPENDED';

    protected string $guard_name = 'web';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'mobile',
        'username',
        'user_type',
        'status',
        'email_verified_at',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->user_type === self::TYPE_SUPER_ADMIN || $this->hasRole('super_admin');
    }

    public function isCustomer(): bool
    {
        return $this->user_type === self::TYPE_CUSTOMER;
    }

    public function isProviderAccount(): bool
    {
        return $this->user_type === self::TYPE_PROVIDER || $this->providerMemberships()->where('status', 'ACTIVE')->exists();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function ownedProviders(): HasMany
    {
        return $this->hasMany(Provider::class, 'owner_user_id');
    }

    public function providerMemberships(): HasMany
    {
        return $this->hasMany(ProviderUser::class, 'user_id');
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class, 'user_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(UserFavorite::class, 'user_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'customer_user_id');
    }

    public function reserveRequests(): HasMany
    {
        return $this->hasMany(ReserveRequest::class, 'customer_user_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(WorkspaceReview::class, 'user_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'user_id');
    }
}
