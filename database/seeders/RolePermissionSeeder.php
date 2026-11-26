<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'admin.read',
            'admin.providers.manage',
            'admin.workspaces.approve',
            'admin.master_data.manage',
            'admin.bookings.manage',
            'admin.coupons.manage',
            'admin.payments.view',
            'admin.support.manage',
            'admin.settings.manage',
            'admin.audit_logs.view',
            'admin.reviews.manage',
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
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->syncPermissions($permissions);

        $providerOwner = Role::findOrCreate('provider_owner', 'web');
        $providerOwner->syncPermissions(array_values(array_filter(
            $permissions,
            fn (string $p) => str_starts_with($p, 'providers.')
        )));

        $providerStaff = Role::findOrCreate('provider_staff', 'web');
        $providerStaff->syncPermissions(['providers.dashboard.view']);

        Role::findOrCreate('customer', 'web');
    }
}
