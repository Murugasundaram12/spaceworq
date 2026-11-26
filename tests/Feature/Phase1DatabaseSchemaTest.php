<?php

namespace Tests\Feature;

use App\Models\Amenity;
use App\Models\City;
use App\Models\Provider;
use App\Models\ProviderUser;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceSubcategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase1DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_expected_tables_are_migrated_on_isolated_sqlite_memory(): void
    {
        $expectedTables = [
            'users',
            'password_reset_tokens',
            'sessions',
            'personal_access_tokens',
            'roles',
            'permissions',
            'otp_verifications',
            'activation_tokens',
            'cities',
            'localities',
            'workspace_categories',
            'workspace_subcategories',
            'amenities',
            'providers',
            'provider_documents',
            'provider_bank_accounts',
            'provider_settings',
            'workspaces',
            'provider_roles',
            'provider_users',
            'workspace_listing_configs',
            'workspace_working_hours',
            'workspace_images',
            'workspace_amenities',
            'workspace_floors',
            'layout_templates',
            'floor_template_mappings',
            'workspace_approval_histories',
            'customer_profiles',
            'user_favorites',
            'workspace_reviews',
            'coupons',
            'reserve_requests',
            'bookings',
            'booking_items',
            'inventory_reservations',
            'coupon_usages',
            'payment_transactions',
            'webhook_events',
            'cancellation_records',
            'refunds',
            'consultations',
            'partner_inquiries',
            'support_tickets',
            'notifications',
            'audit_logs',
            'system_settings',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing expected table: {$table}");
        }
    }

    public function test_master_data_seeder_seeds_verified_reference_data_and_no_fake_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals(6, City::count());
        $this->assertEquals(4, WorkspaceCategory::count());
        $this->assertEquals(10, WorkspaceSubcategory::count());
        $this->assertEquals(16, Amenity::count());
        $this->assertTrue(Role::where('name', 'super_admin')->exists());
        $this->assertTrue(Role::where('name', 'provider_owner')->exists());
        $this->assertTrue(Role::where('name', 'customer')->exists());

        $this->assertEquals(0, User::count());
        $this->assertEquals(0, Provider::count());
        $this->assertEquals(0, ProviderUser::count());
        $this->assertFalse((bool) SystemSetting::getValue('pay_at_venue_enabled', false));
        $this->assertNull(SystemSetting::getValue('default_tax_rate'));
    }
}
