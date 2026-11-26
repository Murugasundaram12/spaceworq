<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Consultation;
use App\Models\Provider;
use App\Models\SupportTicket;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceListingConfig;
use App\Models\WorkspaceSubcategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase7SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $customer;

    private User $providerOwner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::factory()->create([
            'user_type' => User::TYPE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->admin->assignRole('super_admin');

        $this->customer = User::factory()->create([
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->customer->assignRole('customer');

        $this->providerOwner = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->providerOwner->assignRole('provider_owner');
    }

    public function test_non_admin_is_blocked_from_super_admin_routes(): void
    {
        Sanctum::actingAs($this->customer);
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
        $this->getJson('/api/v1/admin/providers')->assertStatus(403);

        Sanctum::actingAs($this->providerOwner);
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_super_admin_kyc_workspace_approval_publishing_coupons_settings_and_audit_logs(): void
    {
        $city = City::where('slug', 'bengaluru')->firstOrFail();
        $cat = WorkspaceCategory::where('code', WorkspaceCategory::CODE_DAY_PASS)->firstOrFail();
        $sub = WorkspaceSubcategory::where('code', 'HOT_DESK_DAY_PASS')->firstOrFail();

        $provider = Provider::create([
            'owner_user_id' => $this->providerOwner->id,
            'company_name' => 'Gateway Cowork Pvt Ltd',
            'slug' => 'gateway-cowork',
            'brand_name' => 'GatewaySpaces',
            'business_email' => 'ops@gateway.test',
            'business_phone' => '9800000301',
            'city_id' => $city->id,
            'status' => Provider::STATUS_PENDING,
            'kyc_status' => Provider::KYC_PENDING,
        ]);

        $workspace = Workspace::create([
            'provider_id' => $provider->id,
            'code' => 'DP-BLR9001',
            'slug' => 'gateway-residency-road',
            'name' => 'Gateway Residency Road',
            'category_id' => $cat->id,
            'subcategory_id' => $sub->id,
            'city_id' => $city->id,
            'address_line' => 'Residency Road, Bengaluru',
            'total_floors' => 2,
            'total_seats' => 40,
            'available_seats' => 40,
            'status' => Workspace::STATUS_IN_REVIEW,
            'approval_status' => Workspace::APPROVAL_PENDING,
            'is_published' => false,
            'is_instant_bookable' => true,
            'created_by' => $this->providerOwner->id,
        ]);

        WorkspaceListingConfig::create([
            'workspace_id' => $workspace->id,
            'subcategory_id' => $sub->id,
            'price_per_day' => 650.00,
        ]);

        Sanctum::actingAs($this->admin);

        // Cannot approve workspace until provider KYC is verified & provider active
        $this->postJson("/api/v1/admin/workspaces/{$workspace->id}/decision", [
            'decision' => 'APPROVE',
        ])->assertStatus(422);

        // Before approval, public API must hide it
        $this->getJson('/api/v1/workspaces/published?city=bengaluru')
            ->assertOk()
            ->assertJsonMissing(['id' => $workspace->id]);

        // Verify Provider KYC -> Provider becomes ACTIVE + KYC_VERIFIED
        $this->postJson("/api/v1/admin/providers/{$provider->id}/verify-kyc", [
            'kyc_status' => Provider::KYC_VERIFIED,
            'kyc_notes' => 'GST and PAN documents verified.',
        ])->assertOk()
            ->assertJsonPath('data.kyc_status', Provider::KYC_VERIFIED)
            ->assertJsonPath('data.status', Provider::STATUS_ACTIVE);

        // Now Super Admin approves workspace -> becomes published on public catalog
        $this->postJson("/api/v1/admin/workspaces/{$workspace->id}/decision", [
            'decision' => 'APPROVE',
            'notes' => 'Approved for listing.',
        ])->assertOk()
            ->assertJsonPath('data.is_published', true)
            ->assertJsonPath('data.approval_status', Workspace::APPROVAL_APPROVED);

        $this->getJson('/api/v1/workspaces/published?city=bengaluru')
            ->assertOk()
            ->assertJsonFragment(['code' => 'DP-BLR9001']);

        // Coupon CRUD + Setting Update
        $this->postJson('/api/v1/admin/coupons', [
            'code' => 'LAUNCH10',
            'title' => 'Launch Offer 10%',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'is_active' => true,
        ])->assertCreated()->assertJsonPath('data.code', 'LAUNCH10');

        $this->putJson('/api/v1/admin/settings', [
            'key' => 'booking_hold_minutes',
            'value' => '15',
            'value_type' => 'integer',
            'group' => 'booking',
        ])->assertOk();

        $this->assertSame(15, SystemSetting::getValue('booking_hold_minutes'));

        // Consultation & Support Ticket moderation + Audit logs
        $consult = Consultation::create([
            'name' => 'Corporate Lead',
            'company_name' => 'Corporate Lead Pvt Ltd',
            'phone' => '9888888888',
            'city_name' => 'Bengaluru',
            'status' => 'NEW',
        ]);

        $ticket = SupportTicket::create([
            'ticket_code' => 'SUP-ADM00001',
            'user_id' => $this->customer->id,
            'category' => 'booking',
            'subject' => 'Receipt copy',
            'message' => 'Need receipt',
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        $this->patchJson("/api/v1/admin/consultations/{$consult->id}", ['status' => 'CONTACTED'])
            ->assertOk()
            ->assertJsonPath('data.status', 'CONTACTED');

        $this->patchJson("/api/v1/admin/support-tickets/{$ticket->id}", [
            'status' => SupportTicket::STATUS_RESOLVED,
            'resolution_notes' => 'Sent tax receipt via email.',
        ])->assertOk()->assertJsonPath('data.status', SupportTicket::STATUS_RESOLVED);

        // Suspending provider unpublishes all its workspaces immediately
        $this->patchJson("/api/v1/admin/providers/{$provider->id}/status", [
            'status' => Provider::STATUS_SUSPENDED,
            'notes' => 'Compliance review',
        ])->assertOk();

        $this->getJson('/api/v1/workspaces/published?city=bengaluru')
            ->assertOk()
            ->assertJsonMissing(['id' => $workspace->id]);

        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['metrics', 'recent_audit_logs']]);

        $this->getJson('/api/v1/admin/audit-logs')
            ->assertOk();
    }
}
