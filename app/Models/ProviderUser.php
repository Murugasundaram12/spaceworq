<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderUser extends Model
{
    use SoftDeletes;

    protected $table = 'provider_users';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $fillable = [
        'provider_id',
        'user_id',
        'workspace_id',
        'provider_role_id',
        'role_name',
        'is_owner',
        'status',
        'invited_by',
    ];

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function providerRole(): BelongsTo
    {
        return $this->belongsTo(ProviderRole::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->is_owner) {
            return true;
        }

        $permissions = $this->providerRole?->permissions ?? [];

        $aliasMap = [
            'providers.workspaceManagement.view' => 'Manage Workspaces',
            'providers.floors.view' => 'Manage Workspaces',
            'providers.users.view' => 'Manage Users',
            'providers.roleManagement.view' => 'Manage Roles',
            'providers.reserveRequests.view' => 'Approve Bookings',
            'providers.instantBookingRequests.view' => 'Approve Bookings',
            'providers.reports.view' => 'View Reports',
            'providers.layoutDesigner.view' => 'Layout Designer',
            'providers.templateMapping.view' => 'Layout Designer',
        ];

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        if (isset($aliasMap[$permission]) && in_array($aliasMap[$permission], $permissions, true)) {
            return true;
        }

        $reverse = array_keys($aliasMap, $permission, true);
        foreach ($reverse as $canonical) {
            if (in_array($canonical, $permissions, true)) {
                return true;
            }
        }

        return false;
    }
}
