<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Provider;
use App\Models\ProviderDocument;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceFloor;
use App\Models\WorkspaceSubcategory;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Phase4BladeIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private City $city;
    private WorkspaceCategory $category;
    private WorkspaceSubcategory $subcategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->city = City::create([
            'name' => 'Bengaluru',
            'slug' => 'bengaluru',
            'state' => 'Karnataka',
            'country' => 'India',
            'is_featured' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->category = WorkspaceCategory::create([
            'code' => 'LONG_TERM_LEASING',
            'name' => 'Long-term Leasing',
            'default_booking_mode' => 'RESERVE_REQUEST',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->subcategory = WorkspaceSubcategory::create([
            'category_id' => $this->category->id,
            'code' => 'NEW_BUILDING',
            'name' => 'New Building',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_all_public_website_blade_pages_render_and_submit_inquiries(): void
    {
        $publicRoutes = [
            route('home') => ['id="heroSection"', 'id="consultationForm"', 'website/css/style.css'],
            route('website.expansion') => ['id="expResultsContainer"', 'id="expCityPillBtn"', 'name="category_interest"'],
            route('website.workspace-providers') => ['id="partnerListingForm"', 'WORKSPACE_PROVIDER'],
            route('website.building-owners') => ['id="buildingSubmitForm"', 'BUILDING_OWNER'],
            route('website.developers') => ['id="projectSubmitForm"', 'DEVELOPER'],
        ];

        foreach ($publicRoutes as $url => $markers) {
            $resp = $this->get($url);
            $resp->assertOk();
            $resp->assertDontSee('Pending Phase 4 view');
            $resp->assertSee('name="csrf-token"', false);
            foreach ($markers as $marker) {
                $resp->assertSee($marker, false);
            }
        }

        // Submit public consultation form via web route
        $this->post(route('website.consultations.store'), [
            'consultName' => 'Meera Iyer',
            'consultCompany' => 'Nexa Cloud',
            'consultPhone' => '9876543210',
            'consultCity' => 'Bengaluru',
            'consultSeats' => '50 - 200 Seats',
            'category_interest' => 'Workspace Provider (Listing Inventory)',
        ])->assertRedirect();

        $this->assertDatabaseHas('consultations', [
            'name' => 'Meera Iyer',
            'company_name' => 'Nexa Cloud',
            'category_interest' => 'Workspace Provider (Listing Inventory)',
        ]);

        // Submit partner inquiry form via web route
        $this->post(route('website.partner-inquiries.store'), [
            'inquiry_type' => 'WORKSPACE_PROVIDER',
            'centreName' => 'Prime Hive Hub',
            'providerCity' => 'Bengaluru',
            'locality' => 'HSR Layout',
            'totalDesks' => 120,
            'deskPrice' => 8500,
            'contactPerson' => 'Arjun Nair',
            'contactPhone' => '9876501234',
            'contactEmail' => 'arjun@primehive.test',
        ])->assertRedirect();

        $this->assertDatabaseHas('partner_inquiries', [
            'inquiry_type' => 'WORKSPACE_PROVIDER',
            'entity_name' => 'Prime Hive Hub',
        ]);
    }

    public function test_customer_auth_blade_pages_render_and_authenticate_without_tokens(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="loginForm"', false)
            ->assertSee('id="resendForm"', false)
            ->assertSee(route('password.request'), false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('id="registerForm"', false)
            ->assertSee('name="terms_accepted"', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('id="forgotPasswordForm"', false);

        $regResp = $this->post(route('customer.register.submit'), [
            'first_name' => 'Kiran',
            'last_name' => 'Rao',
            'email' => 'kiran.blade@test.local',
            'mobile' => '9988776655',
            'password' => 'StrongPass@123',
            'password_confirmation' => 'StrongPass@123',
            'terms_accepted' => '1',
        ]);
        $regResp->assertRedirect(route('home', [], false));

        $user = User::query()->where('email', 'kiran.blade@test.local')->firstOrFail();
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertAuthenticatedAs($user, 'web');

        $token = Password::broker()->createToken($user);
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('id="resetPasswordForm"', false);

        $this->post(route('customer.login.submit'), [
            'email' => 'kiran.blade@test.local',
            'password' => 'StrongPass@123',
        ])->assertRedirect(route('home', [], false));

        $this->get(route('customer.profile.show'))
            ->assertOk()
            ->assertSee('id="customerProfileForm"', false)
            ->assertDontSee('Bearer ');

        $this->patch(route('customer.profile.update'), [
            'first_name' => 'Kiran',
            'last_name' => 'Kumar',
            'mobile' => '9988776655',
            'company_name' => 'Beta Co',
            'preferred_city_id' => $this->city->id,
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $user->id,
            'company_name' => 'Beta Co',
        ]);
    }

    public function test_vendor_auth_onboarding_lockout_and_operational_blade_pages(): void
    {
        // Vendor auth views render cleanly & preserve 6-field registration
        $this->get(route('provider.login'))
            ->assertOk()
            ->assertSee('vendor-panel/css/auth.css', false)
            ->assertSee('id="loginForm"', false);

        $regPage = $this->get(route('provider.register'))->assertOk();
        foreach (['first_name', 'last_name', 'email', 'mobile', 'password', 'confirm_password'] as $fld) {
            $regPage->assertSee("name=\"{$fld}\"", false);
        }

        $this->get(route('provider.password.request'))
            ->assertOk()
            ->assertSee('id="providerForgotForm"', false);

        // Register a pending vendor
        $this->post(route('provider.register.submit'), [
            'first_name' => 'Rohan',
            'last_name' => 'Mehta',
            'email' => 'rohan.vendor@test.local',
            'mobile' => '9123456780',
            'password' => 'PartnerPass@123',
            'confirm_password' => 'PartnerPass@123',
        ])->assertRedirect(route('provider.login'));

        $vendorUser = User::query()->where('email', 'rohan.vendor@test.local')->firstOrFail();
        $vendorUser->forceFill(['status' => User::STATUS_ACTIVE, 'email_verified_at' => now()])->save();
        $provider = Provider::query()->where('owner_user_id', $vendorUser->id)->firstOrFail();

        // Login as pending vendor -> redirects to Company Profile & KYC onboarding page; operational routes blocked
        $this->post(route('provider.login.submit'), [
            'identifier' => 'rohan.vendor@test.local',
            'password' => 'PartnerPass@123',
        ])->assertRedirect(route('provider.profile.show'));

        $this->get(route('provider.status'))
            ->assertOk()
            ->assertSee($provider->statusLabel())
            ->assertDontSee('Reserve Requests')
            ->assertDontSee('Workspace Management');

        $this->get(route('provider.profile.show'))
            ->assertOk()
            ->assertSee('id="providerProfileForm"', false)
            ->assertSee('id="providerKycUploadForm"', false);

        $this->get(route('provider.dashboard'))->assertRedirect(route('provider.profile.show'));
        $this->get(route('provider.workspaces.index'))->assertRedirect(route('provider.profile.show'));
        $this->getJson(route('provider.dashboard'))->assertStatus(403);
        $this->getJson(route('provider.workspaces.index'))->assertStatus(403);

        // Upload KYC & update profile as pending vendor
        Storage::fake('local');
        $this->patch(route('provider.profile.update'), [
            'company_name' => 'Mehta Workspaces Pvt Ltd',
            'brand_name' => 'MehtaHub',
            'business_email' => 'ops@mehtahub.test',
            'business_phone' => '9123456780',
            'address' => '12 MG Road, Bengaluru',
            'city_id' => $this->city->id,
        ])->assertSessionHas('status');

        $this->post(route('provider.documents.store'), [
            'document_type' => 'GST_CERTIFICATE',
            'document' => UploadedFile::fake()->create('gst.pdf', 50, 'application/pdf'),
        ])->assertSessionHas('status');

        $doc = ProviderDocument::query()->where('provider_id', $provider->id)->firstOrFail();
        $this->get(route('provider.documents.download', $doc))->assertOk();

        // Approve provider AND verify KYC -> all in-scope operational Blade pages unlock
        $provider->forceFill([
            'status' => Provider::STATUS_ACTIVE,
            'kyc_status' => Provider::KYC_VERIFIED,
        ])->save();

        $this->get(route('provider.dashboard'))
            ->assertOk()
            ->assertSee('kpiAllWorkspaces', false)
            ->assertDontSee('Reserve Requests')
            ->assertDontSee('Instant Booking Requests');

        $this->get(route('provider.workspaces.index'))
            ->assertOk()
            ->assertSee('id="newWorkspaceDrawer"', false)
            ->assertSee('id="newWorkspaceForm"', false);

        $this->get(route('provider.users.index'))
            ->assertOk()
            ->assertSee('id="registerUserForm"', false);

        $this->get(route('provider.roles.index'))
            ->assertOk()
            ->assertSee('id="newRoleForm"', false);

        $this->get(route('provider.templates.index'))
            ->assertOk()
            ->assertSee('id="tmCanvasBoard"', false)
            ->assertSee('vendor-panel/css/reports.css', false);

        $this->get(route('provider.template-mapping.index'))
            ->assertOk()
            ->assertSee('id="tmMapWorkspacesContainer"', false);

        // Approved vendor creates workspace, role, template & maps template to floor via web routes
        $this->post(route('provider.workspaces.store'), [
            'name' => 'Mehta Koramangala Hub',
            'category_id' => $this->category->id,
            'subcategory_id' => $this->subcategory->id,
            'city_id' => $this->city->id,
            'address_line' => '80 Feet Road, Koramangala, Bengaluru',
            'total_floors' => 2,
            'total_seats' => 80,
            'available_seats' => 80,
            'status' => Workspace::STATUS_DRAFT,
        ])->assertSessionHas('status');

        $ws = Workspace::query()->where('provider_id', $provider->id)->firstOrFail();
        $floor = WorkspaceFloor::query()->where('workspace_id', $ws->id)->firstOrFail();

        $this->post(route('provider.roles.store'), [
            'name' => 'Floor Supervisor',
            'description' => 'Manages floor layout and team',
            'permissions' => ['Manage Workspaces', 'Layout Designer'],
        ])->assertSessionHas('status');

        $this->postJson(route('provider.templates.store'), [
            'name' => 'Open Bay 40',
            'description' => 'Standard 40-seat floor layout',
            'elements' => [
                ['type' => 'desk', 'label' => 'Desk Bay 1', 'x' => 48, 'y' => 48, 'width' => 120, 'height' => 72, 'seats' => 4, 'color' => '#dbeafe'],
            ],
        ])->assertCreated();

        $templateId = \App\Models\LayoutTemplate::query()->where('provider_id', $provider->id)->value('id');
        $this->postJson(route('provider.template-mapping.store'), [
            'workspace_id' => $ws->id,
            'workspace_floor_id' => $floor->id,
            'layout_template_id' => $templateId,
        ])->assertCreated();

        $this->assertDatabaseHas('floor_template_mappings', [
            'workspace_floor_id' => $floor->id,
            'layout_template_id' => $templateId,
        ]);

        // Suspend vendor -> onboarding becomes read-only & operational blocked
        $provider->forceFill(['status' => Provider::STATUS_SUSPENDED])->save();
        $this->get(route('provider.profile.show'))
            ->assertOk()
            ->assertSee('Read-Only Access')
            ->assertDontSee('id="providerKycUploadForm"', false);
        $this->get(route('provider.workspaces.index'))->assertStatus(403);
        $this->getJson(route('provider.workspaces.index'))->assertStatus(403);
    }

    public function test_super_admin_blade_pages_and_decisions_work_and_block_non_admins(): void
    {
        $admin = User::factory()->create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'name' => 'Super Admin',
            'email' => 'superadmin.blade@test.local',
            'password' => Hash::make('AdminPass@123'),
            'user_type' => User::TYPE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('super_admin');

        $owner = User::factory()->create([
            'email' => 'owner.adminreview@test.local',
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        $owner->assignRole('provider_owner');

        $provider = Provider::create([
            'owner_user_id' => $owner->id,
            'company_name' => 'Review Corp',
            'slug' => 'review-corp',
            'business_email' => 'review@corp.test',
            'business_phone' => '9876500001',
            'status' => Provider::STATUS_PENDING,
            'kyc_status' => Provider::KYC_PENDING,
            'city_id' => $this->city->id,
        ]);

        $doc = ProviderDocument::create([
            'provider_id' => $provider->id,
            'document_type' => 'GST_CERTIFICATE',
            'original_filename' => 'gst.pdf',
            'disk' => 'local',
            'storage_path' => 'providers/review-corp/gst.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'status' => ProviderDocument::STATUS_PENDING,
        ]);

        // Non-admin blocked from admin portal
        $this->actingAs($owner, 'web')->get(route('admin.dashboard'))->assertStatus(403);
        auth('web')->logout();

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('id="adminLoginForm"', false);

        $this->post(route('admin.login.submit'), [
            'identifier' => 'superadmin.blade@test.local',
            'password' => 'AdminPass@123',
        ])->assertRedirect(route('admin.dashboard'));

        $adminPages = [
            route('admin.dashboard') => 'SpaceWorq Platform Administration',
            route('admin.providers.index') => 'Review Corp',
            route('admin.providers.show', $provider) => route('admin.provider-documents.download', $doc),
            route('admin.workspaces.index') => 'Workspaces Approval',
            route('admin.customers.index') => 'Customers',
            route('admin.consultations.index') => 'Enterprise Consultations',
            route('admin.partner-inquiries.index') => 'Partner Inquiries',
            route('admin.support-tickets.index') => 'Support Tickets',
            route('admin.reviews.index') => 'Reviews Moderation',
            route('admin.master-data.index') => 'id="adminCityStoreForm"',
            route('admin.audit-logs.index') => 'Audit Logs',
        ];

        foreach ($adminPages as $url => $marker) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('Pending Phase 4 view')
                ->assertSee($marker, false);
        }

        // Super Admin verifies KYC and approves provider through UI actions
        $this->post(route('admin.providers.verify-kyc', $provider), [
            'kyc_status' => Provider::KYC_VERIFIED,
            'kyc_notes' => 'All compliance verified',
        ])->assertSessionHas('status');

        $this->patch(route('admin.providers.status', $provider), [
            'status' => Provider::STATUS_ACTIVE,
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('providers', [
            'id' => $provider->id,
            'status' => Provider::STATUS_ACTIVE,
            'kyc_status' => Provider::KYC_VERIFIED,
        ]);

        // Super Admin creates a city via master-data form
        $this->post(route('admin.cities.store'), [
            'name' => 'Hyderabad',
            'state' => 'Telangana',
            'country' => 'India',
            'is_active' => 1,
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('cities', ['name' => 'Hyderabad']);
    }
}
