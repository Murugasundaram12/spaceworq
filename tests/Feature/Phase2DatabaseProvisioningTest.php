<?php

namespace Tests\Feature;

use App\Http\Resources\ProviderResource;
use App\Models\AuditLog;
use App\Models\City;
use App\Models\Consultation;
use App\Models\PartnerInquiry;
use App\Models\Provider;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceReview;
use App\Models\WorkspaceSubcategory;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Phase2DatabaseProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_additive_migration_columns_rollback_and_remigration_preserve_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $city = City::where('slug', 'bengaluru')->firstOrFail();
        $category = WorkspaceCategory::where('code', WorkspaceCategory::CODE_DAY_PASS)->firstOrFail();
        $subcategory = WorkspaceSubcategory::where('code', 'HOT_DESK_DAY_PASS')->firstOrFail();

        $admin = User::factory()->create([
            'user_type' => User::TYPE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $owner = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $customer = User::factory()->create([
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $provider = Provider::create([
            'owner_user_id' => $owner->id,
            'company_name' => 'Resilient Spaces Ltd',
            'slug' => 'resilient-spaces',
            'brand_name' => 'Resilient',
            'business_email' => 'ops@resilient.test',
            'city_id' => $city->id,
            'submitted_at' => now(),
            'status' => Provider::STATUS_PENDING,
            'kyc_status' => Provider::KYC_NOT_SUBMITTED,
            'status_reason' => 'Initial application queued',
            'status_updated_at' => now(),
            'status_updated_by' => $admin->id,
        ]);

        $workspace = Workspace::create([
            'provider_id' => $provider->id,
            'code' => 'RES-BLR01',
            'slug' => 'resilient-indiranagar',
            'name' => 'Resilient Indiranagar',
            'category_id' => $category->id,
            'subcategory_id' => $subcategory->id,
            'city_id' => $city->id,
            'address_line' => '100 Feet Rd, Bengaluru',
            'total_floors' => 1,
            'total_seats' => 25,
            'available_seats' => 25,
            'status' => Workspace::STATUS_ACTIVE,
            'approval_status' => Workspace::APPROVAL_APPROVED,
            'is_published' => true,
            'created_by' => $owner->id,
        ]);

        $review = WorkspaceReview::create([
            'workspace_id' => $workspace->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'title' => 'Great coworking',
            'comment' => 'Fast internet',
            'is_visible' => true,
            'moderated_by' => $admin->id,
            'moderated_at' => now(),
            'moderation_notes' => 'Verified authentic review',
        ]);

        $consultation = Consultation::create([
            'name' => 'Kiran Rao',
            'company_name' => 'CloudWorks',
            'phone' => '9876501234',
            'city_name' => 'Bengaluru',
            'status' => 'CONTACTED',
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ]);

        $inquiry = PartnerInquiry::create([
            'inquiry_type' => 'WORKSPACE_PROVIDER',
            'entity_name' => 'CloudHub Koramangala',
            'city_name' => 'Bengaluru',
            'contact_name' => 'Kiran Rao',
            'contact_phone' => '9876501234',
            'contact_email' => 'kiran@cloudhub.test',
            'status' => 'NEW',
            'admin_notes' => 'Scheduled call',
            'handled_by' => $admin->id,
            'handled_at' => now(),
        ]);

        $this->assertTrue(Schema::hasColumns('providers', ['submitted_at', 'status_reason', 'status_updated_at', 'status_updated_by']));
        $this->assertTrue(Schema::hasColumns('workspace_reviews', ['moderated_by', 'moderated_at', 'moderation_notes']));
        $this->assertTrue(Schema::hasColumns('consultations', ['handled_by', 'handled_at']));
        $this->assertTrue(Schema::hasColumns('partner_inquiries', ['admin_notes', 'handled_by', 'handled_at']));

        $this->assertSame($admin->id, $provider->statusUpdatedBy->id);
        $this->assertSame($admin->id, $review->moderator->id);
        $this->assertSame($admin->id, $consultation->handler->id);
        $this->assertSame($admin->id, $inquiry->handler->id);

        // Verify nullOnDelete preserves child records when actor user is hard-deleted
        $admin->forceDelete();
        $this->assertNull($provider->fresh()->status_updated_by);
        $this->assertNull($review->fresh()->moderated_by);
        $this->assertNull($consultation->fresh()->handled_by);
        $this->assertNull($inquiry->fresh()->handled_by);

        // Re-test migration, rollback, and re-migration without an outer RefreshDatabase transaction
        // (SQLite ignores PRAGMA foreign_keys = OFF when called inside an active test transaction).
        $defaultConnection = config('database.default');
        config()->set('database.connections.sqlite_ddl_check', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        try {
            config()->set('database.default', 'sqlite_ddl_check');
            app('db')->purge('sqlite_ddl_check');

            Artisan::call('migrate', ['--database' => 'sqlite_ddl_check']);
            (new DatabaseSeeder())->run();

            $isolatedCity = City::where('slug', 'bengaluru')->firstOrFail();
            $isolatedCat = WorkspaceCategory::where('code', WorkspaceCategory::CODE_DAY_PASS)->firstOrFail();
            $isolatedSub = WorkspaceSubcategory::where('code', 'HOT_DESK_DAY_PASS')->firstOrFail();

            $isolatedOwner = User::factory()->create([
                'user_type' => User::TYPE_PROVIDER,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]);
            $isolatedCustomer = User::factory()->create([
                'user_type' => User::TYPE_CUSTOMER,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
            ]);

            $isolatedProvider = Provider::create([
                'owner_user_id' => $isolatedOwner->id,
                'company_name' => 'Rollback Spaces Ltd',
                'slug' => 'rollback-spaces',
                'business_email' => 'ops@rollback.test',
                'city_id' => $isolatedCity->id,
                'submitted_at' => now(),
                'status' => Provider::STATUS_PENDING,
                'kyc_status' => Provider::KYC_NOT_SUBMITTED,
            ]);

            $isolatedWorkspace = Workspace::create([
                'provider_id' => $isolatedProvider->id,
                'code' => 'RB-BLR01',
                'slug' => 'rollback-indiranagar',
                'name' => 'Rollback Indiranagar',
                'category_id' => $isolatedCat->id,
                'subcategory_id' => $isolatedSub->id,
                'city_id' => $isolatedCity->id,
                'address_line' => '100 Feet Rd, Bengaluru',
                'total_floors' => 1,
                'total_seats' => 10,
                'available_seats' => 10,
                'status' => Workspace::STATUS_ACTIVE,
                'approval_status' => Workspace::APPROVAL_APPROVED,
                'is_published' => true,
                'created_by' => $isolatedOwner->id,
            ]);

            $isolatedReview = WorkspaceReview::create([
                'workspace_id' => $isolatedWorkspace->id,
                'user_id' => $isolatedCustomer->id,
                'rating' => 5,
                'title' => 'Great coworking',
                'comment' => 'Fast internet',
                'is_visible' => true,
            ]);

            $isolatedConsultation = Consultation::create([
                'name' => 'Maya Rao',
                'company_name' => 'RollbackWorks',
                'phone' => '9876509999',
                'city_name' => 'Bengaluru',
                'status' => 'NEW',
            ]);

            $isolatedInquiry = PartnerInquiry::create([
                'inquiry_type' => 'WORKSPACE_PROVIDER',
                'entity_name' => 'RollbackHub',
                'city_name' => 'Bengaluru',
                'contact_name' => 'Maya Rao',
                'contact_phone' => '9876509999',
                'contact_email' => 'maya@rollbackhub.test',
                'status' => 'NEW',
            ]);

            Artisan::call('migrate:rollback', ['--database' => 'sqlite_ddl_check', '--step' => 1]);
            $this->assertFalse(Schema::connection('sqlite_ddl_check')->hasColumns('providers', ['submitted_at', 'status_reason', 'status_updated_at', 'status_updated_by']));
            $this->assertFalse(Schema::connection('sqlite_ddl_check')->hasColumns('workspace_reviews', ['moderated_by', 'moderated_at', 'moderation_notes']));
            $this->assertFalse(Schema::connection('sqlite_ddl_check')->hasColumns('consultations', ['handled_by', 'handled_at']));
            $this->assertFalse(Schema::connection('sqlite_ddl_check')->hasColumns('partner_inquiries', ['admin_notes', 'handled_by', 'handled_at']));

            $this->assertDatabaseHas('providers', ['id' => $isolatedProvider->id, 'company_name' => 'Rollback Spaces Ltd'], 'sqlite_ddl_check');
            $this->assertDatabaseHas('workspace_reviews', ['id' => $isolatedReview->id, 'title' => 'Great coworking'], 'sqlite_ddl_check');
            $this->assertDatabaseHas('consultations', ['id' => $isolatedConsultation->id, 'company_name' => 'RollbackWorks'], 'sqlite_ddl_check');
            $this->assertDatabaseHas('partner_inquiries', ['id' => $isolatedInquiry->id, 'entity_name' => 'RollbackHub'], 'sqlite_ddl_check');

            Artisan::call('migrate', ['--database' => 'sqlite_ddl_check']);
            $this->assertTrue(Schema::connection('sqlite_ddl_check')->hasColumns('providers', ['submitted_at', 'status_reason', 'status_updated_at', 'status_updated_by']));
            $this->assertTrue(Schema::connection('sqlite_ddl_check')->hasColumns('workspace_reviews', ['moderated_by', 'moderated_at', 'moderation_notes']));
            $this->assertTrue(Schema::connection('sqlite_ddl_check')->hasColumns('consultations', ['handled_by', 'handled_at']));
            $this->assertTrue(Schema::connection('sqlite_ddl_check')->hasColumns('partner_inquiries', ['admin_notes', 'handled_by', 'handled_at']));
        } finally {
            app('db')->purge('sqlite_ddl_check');
            config()->set('database.default', $defaultConnection);
        }
    }

    public function test_role_permission_and_database_seeder_idempotency_and_zero_users_by_default(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals(0, User::count());
        $this->assertTrue(Permission::where('name', 'admin.reviews.manage')->exists());
        $superAdminRole = Role::findByName('super_admin', 'web');
        $this->assertTrue($superAdminRole->hasPermissionTo('admin.reviews.manage'));

        // Optional SuperAdminSeeder skips safely when env credentials are empty
        config()->set('spaceworq.super_admin.email', null);
        config()->set('spaceworq.super_admin.password', null);
        $this->seed(SuperAdminSeeder::class);
        $this->assertEquals(0, User::count());
    }

    public function test_super_admin_command_does_not_accept_password_option_and_uses_hidden_prompt(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $command = Artisan::all()['spaceworq:create-super-admin'];
        $this->assertFalse($command->getDefinition()->hasOption('password'));

        $this->artisan('spaceworq:create-super-admin')
            ->expectsQuestion('Super Admin full name', 'Platform Admin')
            ->expectsQuestion('Super Admin email address', 'platform.admin@spaceworq.test')
            ->expectsQuestion('Super Admin password (min 8 chars, upper, lower, number, symbol)', 'StrongAdmin@2026!')
            ->expectsQuestion('Confirm Super Admin password', 'StrongAdmin@2026!')
            ->expectsOutputToContain('Super Admin created successfully: platform.admin@spaceworq.test')
            ->assertExitCode(0);

        $admin = User::where('email', 'platform.admin@spaceworq.test')->firstOrFail();
        $this->assertSame(User::TYPE_SUPER_ADMIN, $admin->user_type);
        $this->assertSame(User::STATUS_ACTIVE, $admin->status);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertTrue(Hash::check('StrongAdmin@2026!', $admin->password));

        // Audit log must never store plaintext password
        $audit = AuditLog::where('action', 'admin.super_admin_provisioned')->latest('id')->firstOrFail();
        $this->assertStringNotContainsString('StrongAdmin@2026!', json_encode($audit->metadata));

        // Confirmation mismatch rejects safely without creating a user
        $this->artisan('spaceworq:create-super-admin')
            ->expectsQuestion('Super Admin full name', 'Mismatch Admin')
            ->expectsQuestion('Super Admin email address', 'mismatch.admin@spaceworq.test')
            ->expectsQuestion('Super Admin password (min 8 chars, upper, lower, number, symbol)', 'StrongAdmin@2026!')
            ->expectsQuestion('Confirm Super Admin password', 'DifferentPass@2026!')
            ->expectsOutputToContain('Password confirmation did not match')
            ->assertExitCode(1);

        $this->assertNull(User::where('email', 'mismatch.admin@spaceworq.test')->first());
    }

    public function test_super_admin_command_from_env_update_existing_and_anti_takeover_protections(): void
    {
        $this->seed(RolePermissionSeeder::class);

        // Weak password rejected
        config()->set('spaceworq.super_admin', [
            'name' => 'Env Admin',
            'email' => 'env.admin@spaceworq.test',
            'password' => 'weakpass',
            'mobile' => '9999900001',
            'username' => 'envadmin',
            'seed_on_db_seed' => false,
        ]);

        $this->artisan('spaceworq:create-super-admin', ['--from-env' => true])
            ->assertExitCode(1);
        $this->assertEquals(0, User::count());

        // Strong env credentials succeed
        config()->set('spaceworq.super_admin.password', 'EnvAdmin@2026!');
        $this->artisan('spaceworq:create-super-admin', ['--from-env' => true])
            ->expectsOutputToContain('Super Admin created successfully: env.admin@spaceworq.test')
            ->assertExitCode(0);

        $admin = User::where('email', 'env.admin@spaceworq.test')->firstOrFail();
        $admin->createToken('old-admin-token');
        $this->assertCount(1, $admin->tokens()->get());

        // Running again without --update-existing fails cleanly
        $this->artisan('spaceworq:create-super-admin', ['--from-env' => true])
            ->assertExitCode(1);

        // Running with --update-existing rotates credentials and revokes old tokens
        config()->set('spaceworq.super_admin.password', 'RotatedAdmin@2026!');
        $this->artisan('spaceworq:create-super-admin', ['--from-env' => true, '--update-existing' => true])
            ->expectsOutputToContain('Super Admin updated successfully: env.admin@spaceworq.test')
            ->assertExitCode(0);

        $admin->refresh();
        $this->assertTrue(Hash::check('RotatedAdmin@2026!', $admin->password));
        $this->assertCount(0, $admin->tokens()->get());

        // Prevent account takeover of existing customer or provider accounts
        $customer = User::factory()->create([
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email' => 'customer.victim@spaceworq.test',
            'mobile' => '9888877777',
            'username' => 'custvictim',
        ]);

        config()->set('spaceworq.super_admin.email', 'customer.victim@spaceworq.test');
        config()->set('spaceworq.super_admin.mobile', null);
        config()->set('spaceworq.super_admin.username', null);

        $this->artisan('spaceworq:create-super-admin', ['--from-env' => true, '--update-existing' => true])
            ->assertExitCode(1);

        $customer->refresh();
        $this->assertSame(User::TYPE_CUSTOMER, $customer->user_type);
        $this->assertFalse($customer->hasRole('super_admin'));

        // Also reject mobile collision against provider account
        $providerUser = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email' => 'provider.owner@spaceworq.test',
            'mobile' => '9777766666',
        ]);

        config()->set('spaceworq.super_admin.email', 'brand.new.admin@spaceworq.test');
        config()->set('spaceworq.super_admin.mobile', '9777766666');

        $this->artisan('spaceworq:create-super-admin', ['--from-env' => true])
            ->assertExitCode(1);
        $this->assertNull(User::where('email', 'brand.new.admin@spaceworq.test')->first());
        $this->assertSame(User::TYPE_PROVIDER, $providerUser->fresh()->user_type);

        // Optional SuperAdminSeeder provisions safely and remains idempotent
        config()->set('spaceworq.super_admin', [
            'name' => 'Seeded Admin',
            'email' => 'seeded.admin@spaceworq.test',
            'password' => 'SeededAdmin@2026!',
            'mobile' => '9666655555',
            'username' => 'seededadmin',
            'seed_on_db_seed' => false,
        ]);

        $this->seed(SuperAdminSeeder::class);
        $this->seed(SuperAdminSeeder::class);
        $seededAdmin = User::where('email', 'seeded.admin@spaceworq.test')->firstOrFail();
        $this->assertSame(User::TYPE_SUPER_ADMIN, $seededAdmin->user_type);
        $this->assertTrue($seededAdmin->hasRole('super_admin'));

        // Seeder must also refuse takeover of existing customer email
        config()->set('spaceworq.super_admin.email', 'customer.victim@spaceworq.test');
        config()->set('spaceworq.super_admin.mobile', null);
        config()->set('spaceworq.super_admin.username', null);
        $this->expectException(ValidationException::class);
        $this->seed(SuperAdminSeeder::class);
    }

    public function test_provider_status_constants_labels_and_resource_compatibility(): void
    {
        $this->assertSame('PENDING_VERIFICATION', Provider::STATUS_PENDING);
        $this->assertSame('ACTIVE', Provider::STATUS_ACTIVE);
        $this->assertSame('REJECTED', Provider::STATUS_REJECTED);
        $this->assertSame('SUSPENDED', Provider::STATUS_SUSPENDED);

        $owner = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $provider = Provider::create([
            'owner_user_id' => $owner->id,
            'company_name' => 'Label Co',
            'slug' => 'label-co',
            'business_email' => 'hello@label.test',
            'status' => Provider::STATUS_PENDING,
            'kyc_status' => Provider::KYC_PENDING,
            'status_reason' => 'Awaiting document verification',
            'submitted_at' => now(),
        ]);

        $this->assertTrue($provider->isPending());
        $this->assertFalse($provider->isApproved());
        $this->assertSame('Pending Review', $provider->statusLabel());
        $this->assertSame('Under Review', $provider->kycStatusLabel());

        $payload = (new ProviderResource($provider))->toArray(new Request());
        $this->assertSame('PENDING_VERIFICATION', $payload['status']);
        $this->assertSame('Pending Review', $payload['status_label']);
        $this->assertSame('PENDING', $payload['kyc_status']);
        $this->assertSame('Under Review', $payload['kyc_status_label']);
        $this->assertSame('Awaiting document verification', $payload['status_reason']);
        $this->assertArrayHasKey('company_name', $payload);
        $this->assertArrayHasKey('submitted_at', $payload);
    }
}
