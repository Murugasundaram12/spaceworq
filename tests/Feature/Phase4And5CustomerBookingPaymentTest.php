<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Coupon;
use App\Models\InventoryReservation;
use App\Models\Provider;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceListingConfig;
use App\Models\WorkspaceSubcategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Phase4And5CustomerBookingPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private User $otherCustomer;

    private Workspace $dayPassWorkspace;

    private Workspace $officeWorkspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $owner = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $city = City::where('slug', 'bengaluru')->firstOrFail();
        $catDayPass = WorkspaceCategory::where('code', WorkspaceCategory::CODE_DAY_PASS)->firstOrFail();
        $subHotDesk = WorkspaceSubcategory::where('code', 'HOT_DESK_DAY_PASS')->firstOrFail();
        $catOffice = WorkspaceCategory::where('code', WorkspaceCategory::CODE_LONG_TERM_LEASING)->firstOrFail();
        $subPrivate = WorkspaceSubcategory::where('code', 'COWORKING_SPACE')->firstOrFail();

        $provider = Provider::create([
            'owner_user_id' => $owner->id,
            'company_name' => 'Prime Spaces Pvt Ltd',
            'slug' => 'prime-spaces',
            'brand_name' => 'PrimeSpaces',
            'business_email' => 'ops@primespaces.test',
            'business_phone' => '9800000101',
            'city_id' => $city->id,
            'status' => Provider::STATUS_ACTIVE,
            'kyc_status' => Provider::KYC_VERIFIED,
            'verified_at' => now(),
        ]);

        $this->dayPassWorkspace = Workspace::create([
            'provider_id' => $provider->id,
            'code' => 'DP-BLR0101',
            'slug' => 'koramangala-day-pass',
            'name' => 'Koramangala Day Pass Hub',
            'category_id' => $catDayPass->id,
            'subcategory_id' => $subHotDesk->id,
            'city_id' => $city->id,
            'address_line' => '80ft Road, Koramangala',
            'total_floors' => 2,
            'total_seats' => 10,
            'available_seats' => 5,
            'status' => Workspace::STATUS_ACTIVE,
            'approval_status' => Workspace::APPROVAL_APPROVED,
            'is_published' => true,
            'is_instant_bookable' => true,
            'gst_enabled' => true,
            'tax_rate' => 18.00,
            'created_by' => $owner->id,
        ]);

        WorkspaceListingConfig::create([
            'workspace_id' => $this->dayPassWorkspace->id,
            'subcategory_id' => $subHotDesk->id,
            'price_per_day' => 500.00,
            'price_per_half_day' => 300.00,
            'price_per_hour' => 100.00,
            'min_seats' => 1,
            'max_seats' => 5,
        ]);

        $this->officeWorkspace = Workspace::create([
            'provider_id' => $provider->id,
            'code' => 'CW-BLR0102',
            'slug' => 'hsr-private-cabin',
            'name' => 'HSR Private Cabin Suite',
            'category_id' => $catOffice->id,
            'subcategory_id' => $subPrivate->id,
            'city_id' => $city->id,
            'address_line' => '27th Main, HSR Layout',
            'total_floors' => 1,
            'total_seats' => 20,
            'available_seats' => 20,
            'status' => Workspace::STATUS_ACTIVE,
            'approval_status' => Workspace::APPROVAL_APPROVED,
            'is_published' => true,
            'is_instant_bookable' => false,
            'created_by' => $owner->id,
        ]);

        WorkspaceListingConfig::create([
            'workspace_id' => $this->officeWorkspace->id,
            'subcategory_id' => $subPrivate->id,
            'price_per_day' => 1200.00,
        ]);

        $this->customer = User::factory()->create([
            'first_name' => 'Riya',
            'last_name' => 'Sharma',
            'name' => 'Riya Sharma',
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);

        $this->otherCustomer = User::factory()->create([
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
    }

    public function test_quote_is_server_authoritative_and_booking_prevents_overbooking_and_duplicates(): void
    {
        Coupon::create([
            'code' => 'WORQ20',
            'title' => '20% Off',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 20,
            'max_discount_amount' => 300,
            'min_order_amount' => 500,
            'usage_limit' => 10,
            'per_user_limit' => 1,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->customer);

        $date = now()->addDays(2)->toDateString();

        // Client attempts to inject fake unit_price / total_amount -> server ignores and calculates:
        // 3 seats * 500 = 1500; 20% off capped at 300 => taxable = 1200; 18% GST = 216 => total = 1416
        $quoteRes = $this->postJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book/quote", [
            'booking_date' => $date,
            'seat_count' => 3,
            'slot' => 'fullday',
            'coupon_code' => 'worq20',
            'unit_price' => 1,
            'total_amount' => 1,
        ]);

        $quoteRes->assertOk()
            ->assertJsonPath('data.subtotal_amount', 1500)
            ->assertJsonPath('data.discount_amount', 300)
            ->assertJsonPath('data.taxable_amount', 1200)
            ->assertJsonPath('data.tax_amount', 216)
            ->assertJsonPath('data.total_amount', 1416);

        // Idempotent creation
        $firstCreate = $this->postJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 3,
            'slot' => 'fullday',
            'coupon_code' => 'WORQ20',
            'idempotency_key' => 'idem-book-001',
        ]);

        $firstCreate->assertCreated()
            ->assertJsonPath('data.total_amount', 1416)
            ->assertJsonPath('meta.created', true);

        $bookingId = $firstCreate->json('data.id');

        $duplicateCreate = $this->postJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 3,
            'slot' => 'fullday',
            'idempotency_key' => 'idem-book-001',
        ]);

        $duplicateCreate->assertOk()
            ->assertJsonPath('data.id', $bookingId)
            ->assertJsonPath('meta.created', false);

        $this->assertEquals(3, InventoryReservation::where('workspace_id', $this->dayPassWorkspace->id)->sum('seats_reserved'));

        // Remaining is 2 seats; attempting to book 3 more on same slot must fail
        $this->postJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 3,
            'slot' => 'fullday',
            'idempotency_key' => 'idem-book-002',
        ])->assertStatus(422);

        // Non-day-pass non-instant-bookable workspace rejects instant booking
        $this->postJson("/api/v1/workspaces/published/{$this->officeWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 2,
        ])->assertStatus(422);

        // Another customer cannot view or cancel this booking
        Sanctum::actingAs($this->otherCustomer);
        $this->getJson("/api/v1/bookings/{$bookingId}")->assertStatus(403);
        $this->postJson("/api/v1/bookings/{$bookingId}/cancel")->assertStatus(403);

        // Owner cancels booking -> inventory released
        Sanctum::actingAs($this->customer);
        $this->postJson("/api/v1/bookings/{$bookingId}/cancel", ['reason' => 'Change of plans'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Cancelled');

        // All 5 seats available again
        $this->getJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book/availability?booking_date={$date}&seat_count=5&slot=fullday")
            ->assertOk()
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.remaining_seats', 5);
    }

    public function test_payment_fails_closed_when_unconfigured_and_verifies_signatures_and_webhooks(): void
    {
        Sanctum::actingAs($this->customer);
        $date = now()->addDays(3)->toDateString();

        $bookingId = $this->postJson("/api/v1/workspaces/published/{$this->dayPassWorkspace->id}/instant-book", [
            'booking_date' => $date,
            'seat_count' => 2,
            'slot' => 'fullday',
        ])->assertCreated()->json('data.id');

        // Unconfigured gateway must fail closed with 503
        config()->set('spaceworq.payment.razorpay.key_id', null);
        config()->set('spaceworq.payment.razorpay.key_secret', null);

        $this->postJson("/api/v1/bookings/{$bookingId}/payments/initiate", [
            'idempotency_key' => 'pay-idem-1',
        ])->assertStatus(503);

        // Configure test gateway credentials in memory & fake Razorpay order HTTP API
        config()->set('spaceworq.payment.razorpay.key_id', 'rzp_test_12345');
        config()->set('spaceworq.payment.razorpay.key_secret', 'test_secret_xyz');
        config()->set('spaceworq.payment.razorpay.webhook_secret', 'whsec_test_123');

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_RzpTest001',
                'status' => 'created',
                'amount' => 118000,
            ], 200),
        ]);

        $initRes = $this->postJson("/api/v1/bookings/{$bookingId}/payments/initiate", [
            'idempotency_key' => 'pay-idem-2',
        ]);
        $initRes->assertCreated()
            ->assertJsonPath('data.gateway_order_id', 'order_RzpTest001')
            ->assertJsonPath('meta.gateway_key_id', 'rzp_test_12345');

        // Invalid signature must fail
        $this->postJson("/api/v1/bookings/{$bookingId}/payments/verify", [
            'razorpay_order_id' => 'order_RzpTest001',
            'razorpay_payment_id' => 'pay_RzpTest999',
            'razorpay_signature' => 'invalid_sig',
        ])->assertStatus(422);

        // Valid HMAC signature captures payment and confirms booking
        $validSig = hash_hmac('sha256', 'order_RzpTest001|pay_RzpTest999', 'test_secret_xyz');
        $this->postJson("/api/v1/bookings/{$bookingId}/payments/verify", [
            'razorpay_order_id' => 'order_RzpTest001',
            'razorpay_payment_id' => 'pay_RzpTest999',
            'razorpay_signature' => $validSig,
        ])->assertOk()->assertJsonPath('data.status', 'CAPTURED');

        $this->getJson("/api/v1/bookings/{$bookingId}")
            ->assertOk()
            ->assertJsonPath('data.status', 'Upcoming')
            ->assertJsonPath('data.payment_status', 'PAID');

        // Webhook validation + idempotency
        $webhookPayload = json_encode([
            'id' => 'evt_test_001',
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_RzpTest999',
                        'order_id' => 'order_RzpTest001',
                        'status' => 'captured',
                    ],
                ],
            ],
        ]);

        $this->postJson('/api/v1/webhooks/payments/razorpay', json_decode($webhookPayload, true), [
            'X-Razorpay-Signature' => 'bad_signature',
        ])->assertStatus(403);

        $rawWhSig = hash_hmac('sha256', $webhookPayload, 'whsec_test_123');
        $this->call('POST', '/api/v1/webhooks/payments/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $rawWhSig,
        ], $webhookPayload)->assertOk()->assertJsonPath('data.status', 'processed');

        // Duplicate webhook event must be safely skipped
        $this->call('POST', '/api/v1/webhooks/payments/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $rawWhSig,
        ], $webhookPayload)->assertOk()->assertJsonPath('data.status', 'already_processed');
    }

    public function test_customer_vault_reserve_requests_profile_and_support_tickets(): void
    {
        Sanctum::actingAs($this->customer);

        // Vault favorites
        $this->postJson("/api/v1/workspaces/{$this->officeWorkspace->id}/favorite")
            ->assertCreated()
            ->assertJsonPath('data.saved', true);
        $this->getJson('/api/v1/workspaces/favorites')
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->deleteJson("/api/v1/workspaces/{$this->officeWorkspace->id}/favorite")
            ->assertOk()
            ->assertJsonPath('data.saved', false);

        // Reserve request + reminder
        $resv = $this->postJson("/api/v1/workspaces/published/{$this->officeWorkspace->id}/reserve-request", [
            'requested_seats' => 10,
            'move_in_date' => now()->addDays(14)->toDateString(),
            'duration_months' => 6,
            'request_summary' => 'Need private cabin for 10 engineers',
        ])->assertCreated();

        $reserveId = $resv->json('data.id');

        $this->postJson("/api/v1/workspaces/published/{$this->officeWorkspace->id}/reserve-request/reminder")
            ->assertOk()
            ->assertJsonPath('data.reminders_count', 1);

        $this->getJson("/api/v1/workspaces/reserve-requests/me?buildingId={$this->officeWorkspace->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Customer profile update
        $this->patchJson('/api/v1/customer/profile', [
            'first_name' => 'RiyaUpdated',
            'company_name' => 'Acme Labs India',
            'gstin' => '29ABCDE1234F1Z9',
        ])->assertOk()
            ->assertJsonPath('data.user.first_name', 'RiyaUpdated')
            ->assertJsonPath('data.profile.company_name', 'Acme Labs India');

        // Support ticket creation + isolation
        $ticketRes = $this->postJson('/api/v1/support/tickets', [
            'category' => 'billing',
            'subject' => 'GST Invoice Request',
            'message' => 'Please share GST tax invoice for our booking.',
        ])->assertCreated();

        $ticketId = $ticketRes->json('data.id');

        Sanctum::actingAs($this->otherCustomer);
        $this->getJson("/api/v1/support/tickets/{$ticketId}")->assertStatus(403);
        $this->postJson("/api/v1/workspaces/reserve-requests/{$reserveId}/cancel")->assertStatus(403);
    }
}
