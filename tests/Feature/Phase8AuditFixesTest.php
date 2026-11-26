<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\City;
use App\Models\Coupon;
use App\Models\InventoryReservation;
use App\Models\Provider;
use App\Models\ProviderDocument;
use App\Models\ProviderRole;
use App\Models\ProviderUser;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceListingConfig;
use App\Models\WorkspaceSubcategory;
use App\Notifications\PaymentStatusNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Phase8AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    private User $customerA;

    private User $customerB;

    private User $providerOwner;

    private Provider $provider;

    private Workspace $dayPassWorkspace;

    private Workspace $longTermWorkspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $city = City::where('slug', 'bengaluru')->firstOrFail();
        $catDayPass = WorkspaceCategory::where('code', WorkspaceCategory::CODE_DAY_PASS)->firstOrFail();
        $subHotDesk = WorkspaceSubcategory::where('code', 'HOT_DESK_DAY_PASS')->firstOrFail();
        $catLongTerm = WorkspaceCategory::where('code', WorkspaceCategory::CODE_LONG_TERM_LEASING)->firstOrFail();
        $subNewBuilding = WorkspaceSubcategory::where('code', 'NEW_BUILDING')->firstOrFail();

        $this->providerOwner = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->providerOwner->assignRole('provider_owner');

        $this->provider = Provider::create([
            'owner_user_id' => $this->providerOwner->id,
            'company_name' => 'Audit Spaces Pvt Ltd',
            'slug' => 'audit-spaces',
            'brand_name' => 'AuditSpaces',
            'business_email' => 'ops@auditspaces.test',
            'business_phone' => '9800000401',
            'city_id' => $city->id,
            'status' => Provider::STATUS_ACTIVE,
            'kyc_status' => Provider::KYC_VERIFIED,
            'verified_at' => now(),
        ]);

        $ownerRole = ProviderRole::create([
            'provider_id' => $this->provider->id,
            'name' => 'Provider Owner',
            'permissions' => ProviderRole::CANONICAL_PERMISSIONS,
            'is_system' => true,
        ]);

        ProviderUser::create([
            'provider_id' => $this->provider->id,
            'user_id' => $this->providerOwner->id,
            'provider_role_id' => $ownerRole->id,
            'role_name' => 'Provider Owner',
            'is_owner' => true,
            'status' => ProviderUser::STATUS_ACTIVE,
        ]);

        $this->dayPassWorkspace = Workspace::create([
            'provider_id' => $this->provider->id,
            'code' => 'DP-BLR5001',
            'slug' => 'audit-day-pass',
            'name' => 'Audit Day Pass Hub',
            'category_id' => $catDayPass->id,
            'subcategory_id' => $subHotDesk->id,
            'city_id' => $city->id,
            'address_line' => 'Church Street, Bengaluru',
            'total_floors' => 1,
            'total_seats' => 5,
            'available_seats' => 5,
            'status' => Workspace::STATUS_ACTIVE,
            'approval_status' => Workspace::APPROVAL_APPROVED,
            'is_published' => true,
            'is_instant_bookable' => true,
            'created_by' => $this->providerOwner->id,
        ]);

        WorkspaceListingConfig::create([
            'workspace_id' => $this->dayPassWorkspace->id,
            'subcategory_id' => $subHotDesk->id,
            'price_per_day' => 500.00,
        ]);

        $this->longTermWorkspace = Workspace::create([
            'provider_id' => $this->provider->id,
            'code' => 'LTL-BLR5002',
            'slug' => 'audit-new-building',
            'name' => 'Audit New Tower',
            'category_id' => $catLongTerm->id,
            'subcategory_id' => $subNewBuilding->id,
            'city_id' => $city->id,
            'address_line' => 'Outer Ring Road, Bengaluru',
            'total_floors' => 4,
            'total_seats' => 50,
            'available_seats' => 50,
            'status' => Workspace::STATUS_ACTIVE,
            'approval_status' => Workspace::APPROVAL_APPROVED,
            'is_published' => true,
            'is_instant_bookable' => true, // Misleadingly toggled on, blocked by setting
            'created_by' => $this->providerOwner->id,
        ]);

        WorkspaceListingConfig::create([
            'workspace_id' => $this->longTermWorkspace->id,
            'subcategory_id' => $subNewBuilding->id,
            'price_per_day' => 900.00,
            'price_per_month' => 12000.00,
        ]);

        $this->customerA = User::factory()->create([
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->customerA->assignRole('customer');

        $this->customerB = User::factory()->create([
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $this->customerB->assignRole('customer');

        config()->set('spaceworq.payment.razorpay.key_id', 'rzp_test_audit');
        config()->set('spaceworq.payment.razorpay.key_secret', 'audit_secret_123');
        config()->set('spaceworq.payment.razorpay.webhook_secret', 'audit_whsec_123');
    }

    public function test_expired_hold_cannot_be_verified_or_confirmed_and_frees_capacity(): void
    {
        SystemSetting::query()->updateOrCreate(['key' => 'booking_hold_minutes'], ['value' => '10', 'value_type' => 'integer']);
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::sequence()
                ->push(['id' => 'order_hold_exp_1', 'status' => 'created', 'amount' => 250000], 200)
                ->push(['id' => 'order_cust_b_1', 'status' => 'created', 'amount' => 250000], 200),
        ]);

        Sanctum::actingAs($this->customerA);
        $date = now()->addDays(2)->toDateString();

        $bookingId = $this->postJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 5,
            'slot' => 'fullday',
            'idempotency_key' => 'shared-key',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/bookings/{$bookingId}/payments/initiate", [
            'idempotency_key' => 'shared-pay-key',
        ])->assertCreated();

        // Simulate hold expiration while Customer A tries to pay late
        $booking = Booking::findOrFail($bookingId);
        $booking->forceFill(['hold_expires_at' => now()->subMinutes(2)])->save();
        InventoryReservation::where('booking_id', $booking->id)->update(['expires_at' => now()->subMinutes(2)]);

        // Customer B now books the 5 freed seats using the exact same idempotency_key string cleanly
        Sanctum::actingAs($this->customerB);
        $bookingBId = $this->postJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 5,
            'slot' => 'fullday',
            'idempotency_key' => 'shared-key',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/v1/bookings/{$bookingBId}/payments/initiate", [
            'idempotency_key' => 'shared-pay-key',
        ])->assertCreated();

        $sigB = hash_hmac('sha256', 'order_cust_b_1|pay_cust_b_1', 'audit_secret_123');
        $this->postJson("/api/v1/bookings/{$bookingBId}/payments/verify", [
            'razorpay_order_id' => 'order_cust_b_1',
            'razorpay_payment_id' => 'pay_cust_b_1',
            'razorpay_signature' => $sigB,
        ])->assertOk()->assertJsonPath('data.status', 'CAPTURED');

        // Duplicate verify on Customer B remains idempotent
        $this->postJson("/api/v1/bookings/{$bookingBId}/payments/verify", [
            'razorpay_order_id' => 'order_cust_b_1',
            'razorpay_payment_id' => 'pay_cust_b_1',
            'razorpay_signature' => $sigB,
        ])->assertOk()->assertJsonPath('data.status', 'CAPTURED');

        // Customer A verifies late -> rejected with 422, hold expired, booking cancelled
        Sanctum::actingAs($this->customerA);
        $sigA = hash_hmac('sha256', 'order_hold_exp_1|pay_hold_exp_1', 'audit_secret_123');
        $this->postJson("/api/v1/bookings/{$bookingId}/payments/verify", [
            'razorpay_order_id' => 'order_hold_exp_1',
            'razorpay_payment_id' => 'pay_hold_exp_1',
            'razorpay_signature' => $sigA,
        ])->assertStatus(422);

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_CANCELLED, $booking->status);
        $this->assertEquals(InventoryReservation::STATUS_EXPIRED, InventoryReservation::where('booking_id', $booking->id)->value('status'));
        // Total confirmed seats cannot exceed 5
        $this->assertEquals(5, InventoryReservation::where('workspace_id', $this->dayPassWorkspace->id)->where('status', InventoryReservation::STATUS_CONFIRMED)->sum('seats_reserved'));
    }

    public function test_expired_hold_webhook_does_not_confirm_and_valid_webhook_notifies_and_is_idempotent(): void
    {
        Notification::fake();
        SystemSetting::query()->updateOrCreate(['key' => 'booking_hold_minutes'], ['value' => '10', 'value_type' => 'integer']);
        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::sequence()
                ->push(['id' => 'order_wh_exp', 'status' => 'created', 'amount' => 100000], 200)
                ->push(['id' => 'order_wh_ok', 'status' => 'created', 'amount' => 100000], 200),
        ]);

        Sanctum::actingAs($this->customerA);
        $date = now()->addDays(3)->toDateString();

        // Booking 1 expires before webhook arrives
        $b1Id = $this->postJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 2,
        ])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/bookings/{$b1Id}/payments/initiate", ['idempotency_key' => 'wh-i1'])->assertCreated();

        Booking::whereKey($b1Id)->update(['hold_expires_at' => now()->subMinute()]);
        InventoryReservation::where('booking_id', $b1Id)->update(['expires_at' => now()->subMinute()]);

        $expPayload = json_encode([
            'id' => 'evt_wh_exp_1',
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_wh_exp_1', 'order_id' => 'order_wh_exp', 'status' => 'captured']]],
        ]);
        $expSig = hash_hmac('sha256', $expPayload, 'audit_whsec_123');
        $this->call('POST', '/api/v1/webhooks/payments/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $expSig,
        ], $expPayload)->assertOk()->assertJsonPath('data.status', 'hold_expired');

        $this->assertEquals(Booking::STATUS_CANCELLED, Booking::find($b1Id)->status);
        $this->assertEquals(WebhookEvent::STATUS_FAILED, WebhookEvent::where('event_id', 'evt_wh_exp_1')->value('status'));

        // Booking 2 succeeds via webhook capture -> notifies customer & deduplicates replay
        $b2Id = $this->postJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 2,
        ])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/bookings/{$b2Id}/payments/initiate", ['idempotency_key' => 'wh-i2'])->assertCreated();

        $okPayload = json_encode([
            'id' => 'evt_wh_ok_1',
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_wh_ok_1', 'order_id' => 'order_wh_ok', 'status' => 'captured']]],
        ]);
        $okSig = hash_hmac('sha256', $okPayload, 'audit_whsec_123');

        $this->call('POST', '/api/v1/webhooks/payments/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $okSig,
        ], $okPayload)->assertOk()->assertJsonPath('data.status', 'processed');

        $this->assertEquals(Booking::STATUS_UPCOMING, Booking::find($b2Id)->status);
        $this->assertEquals(Booking::PAYMENT_PAID, Booking::find($b2Id)->payment_status);
        Notification::assertSentTo($this->customerA, PaymentStatusNotification::class);

        $this->call('POST', '/api/v1/webhooks/payments/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $okSig,
        ], $okPayload)->assertOk()->assertJsonPath('data.status', 'already_processed');
    }

    public function test_non_day_pass_instant_book_setting_coupon_per_user_limit_and_expansion_subtype(): void
    {
        Coupon::create([
            'code' => 'ONCEONLY',
            'title' => 'Single use per customer',
            'discount_type' => Coupon::TYPE_FIXED,
            'discount_value' => 100,
            'per_user_limit' => 1,
            'is_active' => true,
        ]);

        // Expansion filters cleanly using ?subtype=NEW_BUILDING
        $this->getJson('/api/v1/expansion-opportunities?subtype=NEW_BUILDING')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['code' => 'LTL-BLR5002']);

        $this->getJson('/api/v1/expansion-opportunities?subtype=OFFICE_COMMERCIAL')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Sanctum::actingAs($this->customerA);
        $date = now()->addDays(4)->toDateString();

        // Even though longTermWorkspace has is_instant_bookable = true, allow_non_day_pass_instant_book=false must block it
        $this->postJson("/api/v1/workspaces/published/{$this->longTermWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 1,
        ])->assertStatus(422);

        // Enabling allow_non_day_pass_instant_book unlocks it
        SystemSetting::query()->updateOrCreate(['key' => 'allow_non_day_pass_instant_book'], ['value' => 'true', 'value_type' => 'boolean']);

        // First booking with coupon passes
        $this->postJson("/api/v1/workspaces/published/{$this->longTermWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 1,
            'coupon_code' => 'ONCEONLY',
        ])->assertCreated();

        // Second booking by same customer on a different workspace exceeds per_user_limit
        $this->postJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 1,
            'coupon_code' => 'ONCEONLY',
        ])->assertStatus(422);
    }

    public function test_provider_kyc_ownership_admin_permissions_json_errors_and_cors(): void
    {
        Storage::fake('local');
        Sanctum::actingAs($this->providerOwner);

        $docId = $this->post('/api/v1/provider/documents', [
            'document_type' => 'GST_CERTIFICATE',
            'document' => UploadedFile::fake()->create('kyc.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.id');

        // Create non-owner provider staff member -> must receive 403 on provider profile and KYC download
        $staffRole = ProviderRole::create([
            'provider_id' => $this->provider->id,
            'name' => 'Floor Coordinator',
            'permissions' => ['providers.dashboard.view'],
            'is_system' => false,
        ]);

        $staffUser = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $staffUser->assignRole('provider_staff');

        ProviderUser::create([
            'provider_id' => $this->provider->id,
            'user_id' => $staffUser->id,
            'provider_role_id' => $staffRole->id,
            'role_name' => 'Floor Coordinator',
            'is_owner' => false,
            'status' => ProviderUser::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($staffUser);
        $this->getJson('/api/v1/provider/profile')->assertStatus(403);
        $this->get("/api/v1/provider/documents/{$docId}/download", ['Accept' => 'application/json'])->assertStatus(403);

        // Super Admin granular permission enforcement
        $admin = User::factory()->create([
            'user_type' => User::TYPE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('super_admin');

        $role = Role::findByName('super_admin', 'web');
        $role->revokePermissionTo('admin.coupons.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/dashboard')->assertOk();
        $this->getJson('/api/v1/admin/coupons')->assertStatus(403);

        // JSON error rendering on /api/* even without Accept: application/json header
        $res404 = $this->get('/api/v1/does-not-exist');
        $res404->assertStatus(404)->assertJsonStructure(['message']);

        // Restrictive CORS check
        $this->get('/api/v1/cities', ['Origin' => 'http://localhost'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost');

        $disallowed = $this->get('/api/v1/cities', ['Origin' => 'https://untrusted.example']);
        $disallowed->assertOk();
        $this->assertNull($disallowed->headers->get('Access-Control-Allow-Origin'));
    }
}
