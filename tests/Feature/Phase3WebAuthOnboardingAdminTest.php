<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\City;
use App\Models\Consultation;
use App\Models\PartnerInquiry;
use App\Models\Provider;
use App\Models\ProviderDocument;
use App\Models\ProviderRole;
use App\Models\ProviderUser;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceReview;
use App\Models\WorkspaceSubcategory;
use App\Notifications\AccountActivationNotification;
use App\Notifications\OtpCodeNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class Phase3WebAuthOnboardingAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_customer_web_session_auth_duplicates_activation_otp_profile_password_reset_and_logout(): void
    {
        Notification::fake();

        // Register customer via web form
        $this->post('/register', [
            'firstName' => 'Alice',
            'lastName' => 'Menon',
            'email' => 'Alice.Menon@Example.COM',
            'mobile' => '9876501122',
            'password' => 'StrongPass@2026!',
            'confirm_password' => 'StrongPass@2026!',
            'terms_accepted' => '1',
        ])->assertRedirect(route('login'));

        $customer = User::where('email', 'alice.menon@example.com')->firstOrFail();
        $this->assertSame(User::TYPE_CUSTOMER, $customer->user_type);
        $this->assertSame(User::STATUS_PENDING_ACTIVATION, $customer->status);
        $this->assertGuest('web');

        // Reject duplicate case-insensitive email and duplicate mobile
        $this->post('/register', [
            'first_name' => 'Duplicate',
            'last_name' => 'Email',
            'email' => 'alice.menon@example.com',
            'mobile' => '9876501133',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms_accepted' => true,
        ])->assertSessionHasErrors('email');

        $this->post('/register', [
            'first_name' => 'Duplicate',
            'last_name' => 'Mobile',
            'email' => 'unique.other@example.com',
            'mobile' => '9876501122',
            'password' => 'StrongPass@2026!',
            'password_confirmation' => 'StrongPass@2026!',
            'terms_accepted' => true,
        ])->assertSessionHasErrors('mobile');

        // Login blocked while pending activation
        $this->post('/login', [
            'identifier' => 'alice.menon@example.com',
            'password' => 'StrongPass@2026!',
        ])->assertSessionHasErrors('identifier');
        $this->assertGuest('web');

        // Resend activation and capture token
        $this->post('/auth/resend-activation', ['email' => 'ALICE.MENON@EXAMPLE.COM'])
            ->assertRedirect();

        $activationToken = null;
        Notification::assertSentTo($customer, AccountActivationNotification::class, function ($n) use (&$activationToken) {
            $ref = new \ReflectionClass($n);
            $prop = $ref->getProperty('token');
            $prop->setAccessible(true);
            $activationToken = $prop->getValue($n);

            return true;
        });

        // Activate via emailed web link
        $this->get('/auth/verify-email?token=' . urlencode($activationToken))
            ->assertRedirect(route('login'));

        $this->assertSame(User::STATUS_ACTIVE, $customer->fresh()->status);

        // Login via web session -> NEVER creates Sanctum personal_access_tokens
        $this->post('/login', [
            'email' => 'alice.menon@example.com',
            'password' => 'StrongPass@2026!',
            'redirect_to' => '/customer/profile',
        ])->assertRedirect('/customer/profile');

        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertEquals(0, PersonalAccessToken::count());

        // Update customer profile
        $city = City::where('slug', 'bengaluru')->firstOrFail();
        $this->patch('/customer/profile', [
            'first_name' => 'Alice',
            'last_name' => 'Sharma',
            'mobile' => '9876501199',
            'company_name' => 'Menon Tech Pvt Ltd',
            'gstin' => '29AAAAA0000A1Z5',
            'preferred_city_id' => $city->id,
            'billing_address' => 'Indiranagar, Bengaluru',
        ])->assertRedirect();

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $customer->id,
            'company_name' => 'Menon Tech Pvt Ltd',
        ]);
        $this->assertSame('Alice Sharma', $customer->fresh()->name);

        // Logout invalidates session
        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest('web');

        // Web OTP login creates session with ZERO Sanctum tokens
        $this->post('/auth/otp/send', ['identifier' => '9876501199'])->assertRedirect();
        $otpCode = null;
        Notification::assertSentTo($customer, OtpCodeNotification::class, function ($n) use (&$otpCode) {
            $ref = new \ReflectionClass($n);
            $prop = $ref->getProperty('otpCode');
            $prop->setAccessible(true);
            $otpCode = $prop->getValue($n);

            return true;
        });

        $this->post('/auth/otp/verify', [
            'identifier' => '9876501199',
            'otp' => $otpCode,
        ])->assertRedirect(route('customer.profile.show', [], false));
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertEquals(0, PersonalAccessToken::count());

        $this->post('/logout')->assertRedirect(route('home'));

        // Customer password reset flow uses /reset-password/{token}
        $this->post('/forgot-password', ['email' => 'alice.menon@example.com'])->assertRedirect();
        $resetUrl = null;
        Notification::assertSentTo($customer, ResetPassword::class, function ($n) use ($customer, &$resetUrl) {
            $resetUrl = $n->toMail($customer)->actionUrl;

            return true;
        });

        $this->assertStringContainsString('/reset-password/', (string) $resetUrl);
        $this->assertStringNotContainsString('/provider/reset-password/', (string) $resetUrl);

        parse_str((string) parse_url((string) $resetUrl, PHP_URL_QUERY), $query);
        $pathParts = explode('/', trim((string) parse_url((string) $resetUrl, PHP_URL_PATH), '/'));
        $resetToken = end($pathParts);

        $this->get('/reset-password/' . $resetToken . '?email=' . urlencode($query['email']))->assertOk();

        $this->post('/reset-password', [
            'token' => $resetToken,
            'email' => 'alice.menon@example.com',
            'password' => 'ResetPass@2026!',
            'password_confirmation' => 'ResetPass@2026!',
        ])->assertRedirect(route('login'));

        $this->post('/login', [
            'identifier' => 'alice.menon@example.com',
            'password' => 'ResetPass@2026!',
        ])->assertRedirect(route('customer.profile.show', [], false));
        $this->assertAuthenticatedAs($customer, 'web');
        $this->assertEquals(0, PersonalAccessToken::count());
    }

    public function test_vendor_six_field_registration_onboarding_kyc_and_strict_status_restrictions(): void
    {
        Notification::fake();
        Storage::fake('local');

        // Exact 6 fields from vendor register.html
        $this->post('/provider/register', [
            'first_name' => 'Vikram',
            'last_name' => 'Rao',
            'email' => 'vikram@operator.test',
            'mobile' => '+91 98400 12345',
            'password' => 'VendorPass@2026!',
            'confirm_password' => 'VendorPass@2026!',
        ])->assertRedirect(route('provider.login'));

        // Duplicate email rejected
        $this->post('/provider/register', [
            'first_name' => 'Dup',
            'last_name' => 'User',
            'email' => 'VIKRAM@operator.test',
            'mobile' => '9840099999',
            'password' => 'VendorPass@2026!',
            'confirm_password' => 'VendorPass@2026!',
        ])->assertSessionHasErrors('email');

        $owner = User::where('email', 'vikram@operator.test')->firstOrFail();
        $provider = Provider::where('owner_user_id', $owner->id)->firstOrFail();
        $this->assertSame(Provider::STATUS_PENDING, $provider->status);
        $this->assertSame(Provider::KYC_NOT_SUBMITTED, $provider->kyc_status);
        $this->assertNull($provider->submitted_at);

        // Extract activation token and activate
        $token = null;
        Notification::assertSentTo($owner, AccountActivationNotification::class, function ($n) use (&$token) {
            $ref = new \ReflectionClass($n);
            $prop = $ref->getProperty('token');
            $prop->setAccessible(true);
            $token = $prop->getValue($n);

            return true;
        });

        $this->get('/auth/activate-provider?token=' . urlencode($token))
            ->assertRedirect(route('provider.login'));

        $owner->refresh();

        // Vendor password reset flow uses /provider/reset-password/{token}
        $this->post('/provider/forgot-password', ['email' => 'vikram@operator.test'])->assertRedirect();
        $vendorResetUrl = null;
        Notification::assertSentTo($owner, ResetPassword::class, function ($n) use ($owner, &$vendorResetUrl) {
            $vendorResetUrl = $n->toMail($owner)->actionUrl;

            return str_contains((string) $vendorResetUrl, '/provider/reset-password/');
        });

        parse_str((string) parse_url((string) $vendorResetUrl, PHP_URL_QUERY), $vQuery);
        $vParts = explode('/', trim((string) parse_url((string) $vendorResetUrl, PHP_URL_PATH), '/'));
        $vendorResetToken = end($vParts);

        $this->get('/provider/reset-password/' . $vendorResetToken . '?email=' . urlencode($vQuery['email']))->assertOk();
        $this->post('/provider/reset-password', [
            'token' => $vendorResetToken,
            'email' => 'vikram@operator.test',
            'password' => 'VendorPass@2026!',
            'password_confirmation' => 'VendorPass@2026!',
        ])->assertRedirect(route('provider.login'));

        // Login pending vendor -> lands on /provider/profile with ZERO Sanctum tokens
        $this->post('/provider/login', [
            'identifier' => 'vikram@operator.test',
            'password' => 'VendorPass@2026!',
        ])->assertRedirect(route('provider.profile.show', [], false));

        $this->assertAuthenticatedAs($owner, 'web');
        $this->assertEquals(0, PersonalAccessToken::count());

        // Pending provider MUST be blocked from operational vendor web routes and operational API routes
        $this->get('/provider/dashboard')->assertRedirect(route('provider.profile.show'));
        $this->get('/provider/workspaces')->assertRedirect(route('provider.profile.show'));
        $this->get('/provider/users')->assertRedirect(route('provider.profile.show'));
        $this->get('/provider/roles')->assertRedirect(route('provider.profile.show'));
        $this->get('/provider/templates')->assertRedirect(route('provider.profile.show'));
        $this->get('/provider/template-mapping')->assertRedirect(route('provider.profile.show'));

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/provider/status')->assertOk()
            ->assertJsonPath('data.provider.status', Provider::STATUS_PENDING)
            ->assertJsonPath('data.can_access_operational_features', false);
        $this->getJson('/api/v1/provider/dashboard')->assertStatus(403);
        $this->getJson('/api/v1/provider/workspaces')->assertStatus(403);
        $this->getJson('/api/v1/provider/staff')->assertStatus(403);
        $this->getJson('/api/v1/provider/roles')->assertStatus(403);
        $this->getJson('/api/v1/provider/templates')->assertStatus(403);

        // Essential onboarding & status routes are accessible to pending owner
        $this->get('/provider/status')->assertOk();
        $this->get('/provider/profile')->assertOk();

        $city = City::where('slug', 'chennai')->firstOrFail();
        $this->patch('/provider/profile', [
            'company_name' => 'Rao Coworking Spaces Pvt Ltd',
            'brand_name' => 'RaoWorq',
            'business_email' => 'ops@raoworq.test',
            'business_phone' => '9840012345',
            'gstin' => '33AAAAA1111A1Z2',
            'pan' => 'AAAAA1111A',
            'city_id' => $city->id,
            'address' => 'Tidel Park, Taramani, Chennai',
        ])->assertRedirect();

        $provider->refresh();
        $this->assertSame('Rao Coworking Spaces Pvt Ltd', $provider->company_name);
        $this->assertNotNull($provider->submitted_at);

        // Invalid MIME upload rejected; valid PDF saved privately on local disk
        $this->post('/provider/documents', [
            'document_type' => 'GST_CERTIFICATE',
            'document' => UploadedFile::fake()->create('script.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('document');

        $this->post('/provider/documents', [
            'document_type' => 'GST_CERTIFICATE',
            'document' => UploadedFile::fake()->create('gst_cert.pdf', 150, 'application/pdf'),
        ])->assertRedirect();

        $doc = ProviderDocument::where('provider_id', $provider->id)->firstOrFail();
        Storage::disk('local')->assertExists($doc->storage_path);
        $this->assertFalse(Storage::disk('public')->exists($doc->storage_path));
        $this->assertSame(Provider::KYC_PENDING, $provider->fresh()->kyc_status);

        // Owner can download own KYC document
        $this->get("/provider/documents/{$doc->id}/download")->assertOk();

        // Safeguard 4: Approving status = ACTIVE without KYC_VERIFIED must NOT unlock operational features
        $admin = $this->createSuperAdmin();
        $this->actingAs($admin, 'web');

        $this->patch("/admin/providers/{$provider->id}/status", [
            'status' => Provider::STATUS_ACTIVE,
            'notes' => 'Status pre-approved pending document check',
        ])->assertRedirect();

        $provider->refresh();
        $this->assertSame(Provider::STATUS_ACTIVE, $provider->status);
        $this->assertSame(Provider::KYC_PENDING, $provider->kyc_status);
        $this->assertFalse($provider->isVerifiedAndActive());
        $this->assertSame($admin->id, $provider->status_updated_by);
        $this->assertNotNull($provider->status_updated_at);

        $this->actingAs($owner, 'web');
        $this->get('/provider/dashboard')->assertRedirect(route('provider.profile.show'));
        $this->getJson('/api/v1/provider/dashboard')->assertStatus(403);
    }

    public function test_rejected_suspended_cross_provider_kyc_and_operational_unlock_lifecycle(): void
    {
        Storage::fake('local');
        $admin = $this->createSuperAdmin();
        $city = City::where('slug', 'bengaluru')->firstOrFail();

        [$ownerA, $providerA] = $this->createProviderAccount('owner-a@test.com', Provider::STATUS_PENDING, Provider::KYC_PENDING, $city->id);
        [$ownerB, $providerB] = $this->createProviderAccount('owner-b@test.com', Provider::STATUS_ACTIVE, Provider::KYC_VERIFIED, $city->id);

        $docA = ProviderDocument::create([
            'provider_id' => $providerA->id,
            'document_type' => 'PAN_CARD',
            'original_filename' => 'pan-a.pdf',
            'disk' => 'local',
            'storage_path' => "providers/{$providerA->id}/kyc/pan-a.pdf",
            'mime_type' => 'application/pdf',
            'size_bytes' => 1200,
            'status' => ProviderDocument::STATUS_PENDING,
        ]);
        Storage::disk('local')->put($docA->storage_path, 'private-kyc-a');

        // Cross-provider KYC download denied for Provider B (Web and API)
        $this->actingAs($ownerB, 'web')
            ->get("/provider/documents/{$docA->id}/download")
            ->assertStatus(403);

        Sanctum::actingAs($ownerB);
        $this->getJson("/api/v1/provider/documents/{$docA->id}/download")->assertStatus(403);

        // Staff non-owner of Provider A also denied from Provider A KYC download
        $staffUser = User::factory()->create([
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $staffRole = ProviderRole::create([
            'provider_id' => $providerA->id,
            'name' => 'Desk Agent',
            'permissions' => ['providers.dashboard.view'],
            'is_system' => false,
        ]);
        ProviderUser::create([
            'provider_id' => $providerA->id,
            'user_id' => $staffUser->id,
            'provider_role_id' => $staffRole->id,
            'role_name' => 'Desk Agent',
            'is_owner' => false,
            'status' => ProviderUser::STATUS_ACTIVE,
        ]);

        $this->actingAs($staffUser, 'web')
            ->get("/provider/documents/{$docA->id}/download")
            ->assertStatus(403);

        // Super Admin rejects Provider A with explicit reason
        $this->actingAs($admin, 'web');
        $this->patch("/admin/providers/{$providerA->id}/status", [
            'status' => Provider::STATUS_REJECTED,
            'notes' => 'PAN document blurry',
        ])->assertRedirect();

        $providerA->refresh();
        $this->assertSame(Provider::STATUS_REJECTED, $providerA->status);
        $this->assertSame('PAN document blurry', $providerA->status_reason);

        // Rejected provider blocked from operational routes, but can inspect status and resubmit KYC
        $this->actingAs($ownerA, 'web');
        $this->get('/provider/dashboard')->assertRedirect(route('provider.profile.show'));
        $this->get('/provider/status')->assertOk();
        $this->post('/provider/documents', [
            'document_type' => 'PAN_CARD',
            'document' => UploadedFile::fake()->create('pan-clear.pdf', 120, 'application/pdf'),
        ])->assertRedirect();

        $this->assertSame(Provider::STATUS_PENDING, $providerA->fresh()->status);

        // Super Admin verifies KYC -> Provider A becomes ACTIVE + KYC_VERIFIED
        $this->actingAs($admin, 'web');
        $this->post("/admin/providers/{$providerA->id}/verify-kyc", [
            'kyc_status' => Provider::KYC_VERIFIED,
            'kyc_notes' => 'All documents verified',
            'document_decisions' => [
                ['id' => $docA->id, 'status' => ProviderDocument::STATUS_APPROVED, 'review_notes' => 'OK'],
            ],
        ])->assertRedirect();

        $this->assertTrue($providerA->fresh()->isVerifiedAndActive());

        // Now Provider A can access operational web and API dashboard + create workspace
        $this->actingAs($ownerA, 'web');
        $this->get('/provider/dashboard')->assertOk();
        Sanctum::actingAs($ownerA);
        $this->getJson('/api/v1/provider/dashboard')->assertOk();

        $cat = WorkspaceCategory::where('code', WorkspaceCategory::CODE_LONG_TERM_LEASING)->firstOrFail();
        $sub = WorkspaceSubcategory::where('code', 'COWORKING_SPACE')->firstOrFail();

        $this->actingAs($ownerA, 'web');
        $this->post('/provider/workspaces', [
            'name' => 'Alpha Verified Hub',
            'category_id' => $cat->id,
            'subcategory_id' => $sub->id,
            'city_id' => $city->id,
            'address_line' => 'MG Road, Bengaluru',
            'total_floors' => 2,
            'total_seats' => 50,
            'available_seats' => 50,
            'status' => Workspace::STATUS_IN_REVIEW,
        ])->assertRedirect();

        $wsA = Workspace::where('provider_id', $providerA->id)->firstOrFail();

        // Cross-provider workspace delete denied
        $this->actingAs($ownerB, 'web')
            ->delete("/provider/workspaces/{$wsA->id}")
            ->assertStatus(403);

        // Super Admin suspends Provider A
        $this->actingAs($admin, 'web');
        $this->patch("/admin/providers/{$providerA->id}/status", [
            'status' => Provider::STATUS_SUSPENDED,
            'notes' => 'Compliance hold',
        ])->assertRedirect();

        // Suspended provider CANNOT modify onboarding data, upload docs, or access operational routes,
        // while read-only /provider/status, /provider/profile, and /provider/logout remain accessible.
        $this->actingAs($ownerA, 'web');
        $this->get('/provider/status')->assertOk();
        $this->get('/provider/profile')->assertOk();
        $this->patch('/provider/profile', [
            'company_name' => 'Attempted Edit While Suspended',
            'business_email' => 'ops@suspended.test',
            'business_phone' => '9876500001',
            'address' => 'Blocked Rd',
        ])->assertStatus(403);
        $this->post('/provider/documents', [
            'document_type' => 'COI',
            'document' => UploadedFile::fake()->create('coi.pdf', 100, 'application/pdf'),
        ])->assertStatus(403);
        $this->get('/provider/dashboard')->assertStatus(403);
        $this->get('/provider/workspaces')->assertStatus(403);

        Sanctum::actingAs($ownerA);
        $this->patchJson('/api/v1/provider/profile', ['company_name' => 'Blocked'])->assertStatus(403);
        $this->getJson('/api/v1/provider/status')->assertOk()
            ->assertJsonPath('data.provider.status', Provider::STATUS_SUSPENDED);
        $this->getJson('/api/v1/provider/dashboard')->assertStatus(403);

        $this->actingAs($ownerA, 'web')
            ->post('/provider/logout')
            ->assertRedirect(route('provider.login'));
    }

    public function test_website_forms_duplicate_web_protection_and_preserved_api_behavior(): void
    {
        // Consultation form with actual UI field names
        $this->post('/consultations', [
            'consultName' => 'Suresh Iyer',
            'consultCompany' => 'Iyer Logistics',
            'consultPhone' => '+91 98450 22334',
            'consultCity' => 'Bengaluru',
            'consultSeats' => '25-50',
        ])->assertRedirect();

        $this->assertDatabaseHas('consultations', [
            'name' => 'Suresh Iyer',
            'company_name' => 'Iyer Logistics',
            'city_name' => 'Bengaluru',
        ]);

        // Duplicate web consultation submission rejected cleanly
        $this->post('/consultations', [
            'consultName' => 'Suresh Iyer',
            'consultCompany' => 'iyer logistics',
            'consultPhone' => '+91 98450 22334',
            'consultCity' => 'bengaluru',
        ])->assertSessionHasErrors('phone');

        // API /api/v1/consultations retains original behavior (not blocked by web duplicate guard)
        $this->postJson('/api/v1/consultations', [
            'name' => 'Suresh Iyer',
            'company_name' => 'Iyer Logistics',
            'phone' => '+91 98450 22334',
            'city_name' => 'Bengaluru',
        ])->assertCreated();

        // Partner inquiry forms: WORKSPACE_PROVIDER, BUILDING_OWNER, DEVELOPER
        $this->post('/partner-inquiries', [
            'inquiry_type' => 'WORKSPACE_PROVIDER',
            'centreName' => 'WorkHub Indiranagar',
            'providerCity' => 'Bengaluru',
            'locality' => 'Indiranagar',
            'totalDesks' => 120,
            'deskPrice' => 8500,
            'contactPerson' => 'Meera Dev',
            'contactPhone' => '9845011111',
            'contactEmail' => 'meera@workhub.test',
        ])->assertRedirect();

        $this->post('/partner-inquiries', [
            'inquiry_type' => 'BUILDING_OWNER',
            'bldgName' => 'RMZ Infinity Tower B',
            'bldgCity' => 'Bengaluru',
            'bldgLocality' => 'Old Madras Rd',
            'bldgArea' => 30000,
            'bldgFloorPlate' => 15000,
            'bldgModel' => 'Straight Lease',
            'bldgRent' => '95',
            'bldgContact' => 'Ramesh B',
            'bldgPhone' => '9845022222',
            'bldgEmail' => 'ramesh@rmz.test',
        ])->assertRedirect();

        $this->post('/partner-inquiries', [
            'inquiry_type' => 'DEVELOPER',
            'projName' => 'Prestige Tech Skyline',
            'projCity' => 'Hyderabad',
            'projMicroMarket' => 'HITEC City',
            'projTotalArea' => 120000,
            'projFloorPlate' => 40000,
            'projStage' => 'Under Construction',
            'projDate' => 'Q4 2026',
            'projRent' => '80',
            'projDevBrand' => 'Prestige Group',
            'projContact' => 'Nitin V',
            'projPhone' => '9845033333',
            'projEmail' => 'nitin@prestige.test',
        ])->assertRedirect();

        $this->assertDatabaseHas('partner_inquiries', ['entity_name' => 'WorkHub Indiranagar', 'inquiry_type' => 'WORKSPACE_PROVIDER']);
        $this->assertDatabaseHas('partner_inquiries', ['entity_name' => 'RMZ Infinity Tower B', 'inquiry_type' => 'BUILDING_OWNER']);
        $this->assertDatabaseHas('partner_inquiries', ['entity_name' => 'Prestige Tech Skyline', 'inquiry_type' => 'DEVELOPER']);

        // Web duplicate partner inquiry blocked cleanly
        $this->post('/partner-inquiries', [
            'inquiry_type' => 'WORKSPACE_PROVIDER',
            'entity_name' => 'WorkHub Indiranagar',
            'city_name' => 'Bengaluru',
            'contact_name' => 'Meera Dev',
            'contact_phone' => '9845011111',
            'contact_email' => 'MEERA@workhub.test',
        ])->assertSessionHasErrors('contact_email');
    }

    public function test_portal_isolation_super_admin_web_session_and_review_moderation_permissions(): void
    {
        $admin = $this->createSuperAdmin();
        $customer = User::factory()->create([
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'password' => 'CustPass@2026!',
        ]);
        $customer->assignRole('customer');

        $city = City::where('slug', 'bengaluru')->firstOrFail();
        [$providerUser, $provider] = $this->createProviderAccount('portal-p@test.com', Provider::STATUS_ACTIVE, Provider::KYC_VERIFIED, $city->id);

        // Cross-portal login rejection
        $this->post('/provider/login', ['identifier' => $customer->email, 'password' => 'CustPass@2026!'])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest('web');

        $this->post('/admin/login', ['identifier' => $customer->email, 'password' => 'CustPass@2026!'])
            ->assertSessionHasErrors('identifier');
        $this->assertGuest('web');

        // Authenticated portal cross-access denied with 403
        $this->actingAs($customer, 'web');
        $this->get('/provider/dashboard')->assertStatus(403);
        $this->get('/admin/dashboard')->assertStatus(403);

        $this->actingAs($providerUser, 'web');
        $this->get('/customer/profile')->assertStatus(403);
        $this->get('/admin/dashboard')->assertStatus(403);

        // Super Admin session login creates ZERO Sanctum tokens and isolates non-admin portals
        $this->post('/admin/logout');
        $this->post('/admin/login', [
            'identifier' => $admin->email,
            'password' => 'SuperAdmin@2026!',
        ])->assertRedirect(route('admin.dashboard', [], false));

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertEquals(0, PersonalAccessToken::count());
        $this->get('/admin/dashboard')->assertOk();
        $this->get('/customer/profile')->assertStatus(403);
        $this->get('/provider/dashboard')->assertStatus(403);

        // Admin consultation & inquiry tracking + review moderation
        $consult = Consultation::create([
            'name' => 'Lead One',
            'company_name' => 'Lead Corp',
            'phone' => '9876540000',
            'city_name' => 'Bengaluru',
            'status' => 'NEW',
        ]);
        $this->patch("/admin/consultations/{$consult->id}", [
            'status' => 'CONTACTED',
            'notes' => 'Initial call completed',
        ])->assertRedirect();

        $consult->refresh();
        $this->assertSame($admin->id, $consult->handled_by);
        $this->assertNotNull($consult->handled_at);

        $partnerInquiry = PartnerInquiry::create([
            'inquiry_type' => 'BUILDING_OWNER',
            'entity_name' => 'Lead Tower',
            'city_name' => 'Bengaluru',
            'contact_name' => 'Lead Contact',
            'contact_phone' => '9876541111',
            'contact_email' => 'lead@tower.test',
            'status' => 'NEW',
        ]);
        $this->patch("/admin/partner-inquiries/{$partnerInquiry->id}", [
            'status' => 'ONBOARDING',
            'admin_notes' => 'Scheduled site walkthrough',
        ])->assertRedirect();

        $partnerInquiry->refresh();
        $this->assertSame('ONBOARDING', $partnerInquiry->status);
        $this->assertSame('Scheduled site walkthrough', $partnerInquiry->admin_notes);
        $this->assertSame($admin->id, $partnerInquiry->handled_by);
        $this->assertNotNull($partnerInquiry->handled_at);

        // Customer status suspension revokes active Sanctum tokens cleanly
        $customer->createToken('customer-mobile');
        $this->patch("/admin/customers/{$customer->id}/status", [
            'status' => User::STATUS_SUSPENDED,
        ])->assertRedirect();
        $this->assertSame(User::STATUS_SUSPENDED, $customer->fresh()->status);
        $this->assertEquals(0, $customer->tokens()->count());

        // Restore customer active for review moderation assertion context
        $this->patch("/admin/customers/{$customer->id}/status", [
            'status' => User::STATUS_ACTIVE,
        ])->assertRedirect();

        $cat = WorkspaceCategory::where('code', WorkspaceCategory::CODE_DAY_PASS)->firstOrFail();
        $sub = WorkspaceSubcategory::where('code', 'HOT_DESK_DAY_PASS')->firstOrFail();
        $workspace = Workspace::create([
            'provider_id' => $provider->id,
            'code' => 'REV-BLR01',
            'slug' => 'review-ws',
            'name' => 'Review Workspace',
            'category_id' => $cat->id,
            'subcategory_id' => $sub->id,
            'city_id' => $city->id,
            'address_line' => 'Lavelle Road, Bengaluru',
            'total_floors' => 1,
            'total_seats' => 15,
            'available_seats' => 15,
            'status' => Workspace::STATUS_ACTIVE,
            'approval_status' => Workspace::APPROVAL_APPROVED,
            'is_published' => true,
            'rating_avg' => 5.0,
            'reviews_count' => 1,
            'created_by' => $providerUser->id,
        ]);

        $review = WorkspaceReview::create([
            'workspace_id' => $workspace->id,
            'user_id' => $customer->id,
            'rating' => 5,
            'title' => 'Spam content',
            'comment' => 'Promotional link',
            'is_visible' => true,
        ]);

        $this->patch("/admin/reviews/{$review->id}/moderate", [
            'is_visible' => false,
            'moderation_notes' => 'Promotional spam hidden',
        ])->assertRedirect();

        $review->refresh();
        $workspace->refresh();
        $this->assertFalse($review->is_visible);
        $this->assertSame($admin->id, $review->moderated_by);
        $this->assertNotNull($review->moderated_at);
        $this->assertSame(0, $workspace->reviews_count);
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.review_moderated', 'auditable_id' => $review->id]);

        // Permission revocation enforcement for admin.reviews.manage across Web and API
        $role = Role::findByName('super_admin', 'web');
        $role->revokePermissionTo('admin.reviews.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->get('/admin/reviews')->assertStatus(403);
        Sanctum::actingAs($admin);
        $this->getJson('/api/v1/admin/reviews')->assertStatus(403);
    }

    public function test_web_routes_enforce_csrf_middleware_and_reject_invalid_tokens(): void
    {
        $route = Route::getRoutes()->getByName('customer.login.submit');
        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());

        // Verify Laravel's CSRF verifier throws TokenMismatchException on unverified web POST
        $verifier = new class(app(), app('encrypter')) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        };

        $badRequest = HttpRequest::create('/login', 'POST', ['identifier' => 'a@b.test']);
        $badRequest->setLaravelSession(app('session')->driver());

        $this->expectException(TokenMismatchException::class);
        $verifier->handle($badRequest, fn () => response('OK'));
    }

    private function createSuperAdmin(): User
    {
        $admin = User::factory()->create([
            'user_type' => User::TYPE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'password' => 'SuperAdmin@2026!',
        ]);
        $admin->assignRole('super_admin');

        return $admin;
    }

    /**
     * @return array{0: User, 1: Provider}
     */
    private function createProviderAccount(string $email, string $status, string $kycStatus, int $cityId): array
    {
        $user = User::factory()->create([
            'email' => $email,
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'password' => 'ProvPass@2026!',
        ]);
        $user->assignRole('provider_owner');

        $provider = Provider::create([
            'owner_user_id' => $user->id,
            'company_name' => 'Provider Co ' . $user->id,
            'slug' => 'provider-co-' . $user->id,
            'business_email' => $email,
            'city_id' => $cityId,
            'status' => $status,
            'kyc_status' => $kycStatus,
            'verified_at' => $kycStatus === Provider::KYC_VERIFIED ? now() : null,
        ]);

        $role = ProviderRole::create([
            'provider_id' => $provider->id,
            'name' => 'Provider Admin',
            'permissions' => ProviderRole::CANONICAL_PERMISSIONS,
            'is_system' => true,
        ]);

        ProviderUser::create([
            'provider_id' => $provider->id,
            'user_id' => $user->id,
            'provider_role_id' => $role->id,
            'role_name' => 'Provider Admin',
            'is_owner' => true,
            'status' => ProviderUser::STATUS_ACTIVE,
        ]);

        return [$user, $provider];
    }
}
