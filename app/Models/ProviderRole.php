<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderRole extends Model
{
    public const CANONICAL_PERMISSIONS = [
        'providers.dashboard.view',
        'providers.workspaceManagement.view',
        'providers.floors.view',
        'providers.users.view',
        'providers.roleManagement.view',
        'providers.reserveRequests.view',
        'providers.instantBookingRequests.view',
        'providers.reports.view',
        'providers.layoutDesigner.view',
        'providers.templateMapping.view',
        'Manage Workspaces',
        'Manage Users',
        'Manage Roles',
        'Approve Bookings',
        'View Reports',
        'Layout Designer',
    ];

    protected $fillable = [
        'provider_id',
        'workspace_id',
        'name',
        'description',
        'permissions',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function providerUsers(): HasMany
    {
        return $this->hasMany(ProviderUser::class);
    }
}
