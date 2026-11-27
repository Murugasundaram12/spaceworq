<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerAutoActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'MasterDataSeeder']);
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /**
     * Tests 1 to 6:
     * 1. New Customer registration succeeds.
     * 2. New Customer account is immediately ACTIVE.
     * 3. Customer role is assigned correctly.
     * 4. Customer is automatically authenticated.
     * 5. Registration redirects to Customer Profile/Dashboard.
     * 6. Customer can access authorized Customer pages (/customer/profile).
     */
    public function test_customer_registration_automatically_activates_authenticates_and_redirects(): void
    {
        Notification::fake();

        $payload = [
            'first_name' => 'Rahul',
            'last_name' => 'Dravid',
            'email' => 'rahul.dravid@example.com',
            'mobile' => '9845012345',
            'password' => 'CricketPass@2026!',
            'password_confirmation' => 'CricketPass@2026!',
            'terms_accepted' => true,
            'company_name' => 'Wall Tech Pvt Ltd',
        ];

        $response = $this->post(route('customer.register.submit'), $payload);

        // 5. Registration redirects to Home (Find a Workspace)
        $response->assertRedirect(route('home', [], false));
        $response->assertSessionHas('status', 'Account registered and activated successfully.');

        // 1. Customer record exists
        $user = User::where('email', 'rahul.dravid@example.com')->firstOrFail();

        // 2. Immediately ACTIVE and email_verified_at populated
        $this->assertSame(User::STATUS_ACTIVE, $user->status);
        $this->assertSame(User::TYPE_CUSTOMER, $user->user_type);
        $this->assertNotNull($user->email_verified_at);

        // 3. Customer role assigned correctly
        $this->assertTrue($user->hasRole('customer'));

        // 4. Automatically authenticated in web session
        $this->assertAuthenticatedAs($user, 'web');

        // 6. Can access authorized customer pages
        $profileResponse = $this->get(route('customer.profile.show'));
        $profileResponse->assertOk();
        $profileResponse->assertSee('Wall Tech Pvt Ltd');
        $profileResponse->assertSee('Rahul Dravid');

        // Test updating profile
        $updateResponse = $this->patch(route('customer.profile.update'), [
            'first_name' => 'Rahul',
            'last_name' => 'Sharath',
            'mobile' => '9845012345',
            'company_name' => 'Wall Tech Global',
        ]);
        $updateResponse->assertRedirect();
        $this->assertSame('Rahul Sharath', $user->fresh()->name);
    }

    /**
     * Test 7: Provider and Super Admin accounts cannot use Customer login.
     */
    public function test_provider_and_super_admin_accounts_cannot_use_customer_login(): void
    {
        // Provider account
        $providerUser = User::factory()->create([
            'email' => 'provider.owner@example.com',
            'user_type' => User::TYPE_PROVIDER,
            'status' => User::STATUS_ACTIVE,
            'password' => bcrypt('StrongPass@2026!'),
        ]);
        $providerUser->assignRole('provider_owner');

        // Super Admin account
        $adminUser = User::factory()->create([
            'email' => 'superadmin@example.com',
            'user_type' => User::TYPE_SUPER_ADMIN,
            'status' => User::STATUS_ACTIVE,
            'password' => bcrypt('StrongPass@2026!'),
        ]);
        $adminUser->assignRole('super_admin');

        // Provider trying customer login -> blocked
        $providerAttempt = $this->post(route('customer.login.submit'), [
            'identifier' => 'provider.owner@example.com',
            'password' => 'StrongPass@2026!',
        ]);
        $providerAttempt->assertSessionHasErrors('identifier');
        $this->assertGuest('web');

        // Super Admin trying customer login -> blocked
        $adminAttempt = $this->post(route('customer.login.submit'), [
            'identifier' => 'superadmin@example.com',
            'password' => 'StrongPass@2026!',
        ]);
        $adminAttempt->assertSessionHasErrors('identifier');
        $this->assertGuest('web');
    }

    /**
     * Test 8: Existing Provider registration and KYC flows remain unchanged.
     */
    public function test_existing_provider_registration_and_kyc_flows_remain_unchanged(): void
    {
        Notification::fake();

        $providerPayload = [
            'first_name' => 'Vikram',
            'last_name' => 'Seth',
            'email' => 'vikram.seth@cowork.in',
            'mobile' => '+91 98400 99999',
            'password' => 'VendorPass@2026!',
            'password_confirmation' => 'VendorPass@2026!',
            'company_name' => 'Seth Workspaces',
            'terms_accepted' => true,
        ];

        $response = $this->post(route('provider.register.submit'), $providerPayload);

        // Provider registration still redirects to provider.login with pending activation message
        $response->assertRedirect(route('provider.login'));
        $response->assertSessionHas('status', 'Partner account created! Please verify your email to activate your account.');

        $providerUser = User::where('email', 'vikram.seth@cowork.in')->firstOrFail();
        $this->assertSame(User::TYPE_PROVIDER, $providerUser->user_type);
        // Provider MUST be PENDING_ACTIVATION initially
        $this->assertSame(User::STATUS_PENDING_ACTIVATION, $providerUser->status);
        $this->assertTrue($providerUser->hasRole('provider_owner'));

        // Provider entity is created with PENDING_VERIFICATION and KYC NOT_SUBMITTED
        $provider = Provider::where('owner_user_id', $providerUser->id)->firstOrFail();
        $this->assertSame(Provider::STATUS_PENDING, $provider->status);
        $this->assertSame(Provider::KYC_NOT_SUBMITTED, $provider->kyc_status);
        $this->assertFalse($provider->isVerifiedAndActive());

        // Unactivated provider cannot log in
        $loginResponse = $this->post(route('provider.login.submit'), [
            'identifier' => 'vikram.seth@cowork.in',
            'password' => 'VendorPass@2026!',
        ]);
        $loginResponse->assertSessionHasErrors('identifier');
        $this->assertGuest('web');
    }

    /**
     * Test 9: Duplicate email registration is rejected.
     */
    public function test_duplicate_email_and_mobile_registration_is_rejected(): void
    {
        // First customer registration
        $this->post(route('customer.register.submit'), [
            'first_name' => 'Original',
            'last_name' => 'User',
            'email' => 'original.user@example.com',
            'mobile' => '9111122222',
            'password' => 'OriginalPass@2026!',
            'password_confirmation' => 'OriginalPass@2026!',
            'terms_accepted' => true,
        ])->assertRedirect(route('home', [], false));

        $this->assertDatabaseHas('users', ['email' => 'original.user@example.com']);

        // Logout
        $this->post('/logout');
        $this->assertGuest('web');

        // Duplicate case-insensitive email attempt
        $dupEmailResponse = $this->post(route('customer.register.submit'), [
            'first_name' => 'Duplicate',
            'last_name' => 'User',
            'email' => 'ORIGINAL.USER@EXAMPLE.COM',
            'mobile' => '9333344444',
            'password' => 'AnotherPass@2026!',
            'password_confirmation' => 'AnotherPass@2026!',
            'terms_accepted' => true,
        ]);
        $dupEmailResponse->assertSessionHasErrors('email');

        // Duplicate mobile attempt
        $dupMobileResponse = $this->post(route('customer.register.submit'), [
            'first_name' => 'Another',
            'last_name' => 'User',
            'email' => 'another.unique@example.com',
            'mobile' => '9111122222',
            'password' => 'AnotherPass@2026!',
            'password_confirmation' => 'AnotherPass@2026!',
            'terms_accepted' => true,
        ]);
        $dupMobileResponse->assertSessionHasErrors('mobile');

        // Total users with original email is exactly 1
        $this->assertSame(1, User::where('email', 'original.user@example.com')->count());
    }

    /**
     * Test 10: Fallback to home vs explicit safe redirect destination.
     */
    public function test_customer_login_and_registration_fallback_and_safe_redirects(): void
    {
        // 1. Existing customer logs in without redirect_to -> redirects to home ('/')
        $customer = User::factory()->create([
            'email' => 'existing.customer@example.com',
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'password' => bcrypt('StrongPass@2026!'),
        ]);
        $customer->assignRole('customer');

        $loginResponse = $this->post(route('customer.login.submit'), [
            'identifier' => 'existing.customer@example.com',
            'password' => 'StrongPass@2026!',
        ]);
        $loginResponse->assertRedirect(route('home', [], false));
        $this->assertAuthenticatedAs($customer, 'web');

        $this->post('/logout');
        $this->assertGuest('web');

        // 2. Existing customer logs in WITH explicit safe redirect_to -> redirects to explicit target
        $explicitLoginResponse = $this->post(route('customer.login.submit'), [
            'identifier' => 'existing.customer@example.com',
            'password' => 'StrongPass@2026!',
            'redirect_to' => '/customer/profile',
        ]);
        $explicitLoginResponse->assertRedirect('/customer/profile');
        $this->assertAuthenticatedAs($customer, 'web');

        $this->post('/logout');
        $this->assertGuest('web');

        // 3. New customer registers WITH explicit safe redirect_to -> redirects to explicit target
        $explicitRegResponse = $this->post(route('customer.register.submit'), [
            'first_name' => 'Redirect',
            'last_name' => 'Test',
            'email' => 'redirect.test@example.com',
            'mobile' => '9444455555',
            'password' => 'RedirectPass@2026!',
            'password_confirmation' => 'RedirectPass@2026!',
            'terms_accepted' => true,
            'redirect_to' => '/expansion',
        ]);
        $explicitRegResponse->assertRedirect('/expansion');

        // 4. Malicious external open redirect attempt -> blocked by RedirectSafetyService, falls back to home
        $this->post('/logout');
        $unsafeLoginResponse = $this->post(route('customer.login.submit'), [
            'identifier' => 'existing.customer@example.com',
            'password' => 'StrongPass@2026!',
            'redirect_to' => 'https://evil.com/phishing',
        ]);
        $unsafeLoginResponse->assertRedirect(route('home', [], false));
    }
}
