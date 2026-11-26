<?php

namespace Tests\Feature;

use App\Models\Provider;
use App\Models\ProviderUser;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Notifications\OtpCodeNotification;
use App\Services\RedirectSafetyService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Phase2AuthenticationRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_registration_requires_activation_before_login(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'Arjun',
            'last_name' => 'Kumar',
            'email' => 'arjun@company.com',
            'mobile' => '9876543210',
            'password' => 'StrongPass@123',
            'password_confirmation' => 'StrongPass@123',
            'terms_accepted' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.requires_activation', true)
            ->assertJsonPath('data.user.user_type', 'customer')
            ->assertJsonPath('data.user.status', 'PENDING_ACTIVATION');

        $user = User::where('email', 'arjun@company.com')->firstOrFail();

        // Login blocked while unactivated
        $this->postJson('/api/v1/auth/customer/login', [
            'identifier' => 'arjun@company.com',
            'password' => 'StrongPass@123',
        ])->assertStatus(403);

        $token = null;
        Notification::assertSentTo($user, AccountActivationNotification::class, function ($notification) use (&$token) {
            $reflection = new \ReflectionClass($notification);
            $prop = $reflection->getProperty('token');
            $prop->setAccessible(true);
            $token = $prop->getValue($notification);
            return true;
        });

        $this->postJson('/api/v1/auth/verify-email', ['token' => $token])
            ->assertOk()
            ->assertJsonPath('data.user.status', 'ACTIVE');

        $loginRes = $this->postJson('/api/v1/auth/customer/login', [
            'identifier' => 'arjun@company.com',
            'password' => 'StrongPass@123',
            'redirect_to' => 'https://evil.example/phish',
        ])->assertOk();

        // Open redirect neutralized
        $loginRes->assertJsonPath('data.redirect_to', '/');
        $this->assertNotEmpty($loginRes->json('data.token'));
    }

    public function test_provider_registration_creates_unified_provider_and_provider_user(): void
    {
        Notification::fake();

        $res = $this->postJson('/api/v1/auth/register-provider', [
            'first_name' => 'Meera',
            'last_name' => 'Nair',
            'email' => 'meera@operator.in',
            'mobile' => '9840122310',
            'password' => 'PartnerPass@123',
            'confirm_password' => 'PartnerPass@123',
            'company_name' => 'CoWork Hub India',
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.user.user_type', 'provider')
            ->assertJsonPath('data.provider.company_name', 'CoWork Hub India')
            ->assertJsonPath('data.provider.status', 'PENDING_VERIFICATION');

        $user = User::where('email', 'meera@operator.in')->firstOrFail();
        $provider = Provider::where('owner_user_id', $user->id)->firstOrFail();

        $this->assertDatabaseHas('provider_users', [
            'provider_id' => $provider->id,
            'user_id' => $user->id,
            'is_owner' => true,
            'status' => 'ACTIVE',
        ]);
    }

    public function test_portal_boundaries_prevent_customer_from_accessing_provider_or_admin_login(): void
    {
        $customer = User::factory()->create([
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'password' => 'Secret@1234',
        ]);
        $customer->assignRole('customer');

        $this->postJson('/api/v1/auth/provider/login', [
            'identifier' => $customer->email,
            'password' => 'Secret@1234',
        ])->assertStatus(403);

        $this->postJson('/api/v1/admin/auth/login', [
            'identifier' => $customer->email,
            'password' => 'Secret@1234',
        ])->assertStatus(403);
    }

    public function test_otp_login_generates_and_verifies_hashed_code(): void
    {
        Notification::fake();

        $customer = User::factory()->create([
            'user_type' => User::TYPE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'mobile' => '9988776655',
        ]);
        $customer->assignRole('customer');

        $this->postJson('/api/v1/auth/send-otp', [
            'identifier' => '9988776655',
        ])->assertOk();

        $otpCode = null;
        Notification::assertSentTo($customer, OtpCodeNotification::class, function ($notification) use (&$otpCode) {
            $reflection = new \ReflectionClass($notification);
            $prop = $reflection->getProperty('otpCode');
            $prop->setAccessible(true);
            $otpCode = $prop->getValue($notification);
            return true;
        });

        $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => '9988776655',
            'otp' => '000000',
        ])->assertStatus(422);

        $this->postJson('/api/v1/auth/verify-otp', [
            'identifier' => '9988776655',
            'otp' => $otpCode,
        ])->assertOk()
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_redirect_safety_service_blocks_external_and_protocol_relative_urls(): void
    {
        $service = app(RedirectSafetyService::class);

        $this->assertSame('/partner/developers', $service->sanitize('/partner/developers'));
        $this->assertSame('/', $service->sanitize('https://attacker.com/login'));
        $this->assertSame('/', $service->sanitize('//attacker.com/login'));
        $this->assertSame('/', $service->sanitize('/\attacker.com'));
    }
}
