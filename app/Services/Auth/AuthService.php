<?php

namespace App\Services\Auth;

use App\Models\ActivationToken;
use App\Models\CustomerProfile;
use App\Models\OtpVerification;
use App\Models\Provider;
use App\Models\ProviderRole;
use App\Models\ProviderSetting;
use App\Models\ProviderUser;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Notifications\OtpCodeNotification;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function registerCustomer(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                'email' => strtolower(trim($data['email'])),
                'mobile' => trim($data['mobile']),
                'user_type' => User::TYPE_CUSTOMER,
                'status' => User::STATUS_PENDING_ACTIVATION,
                'password' => $data['password'],
            ]);

            $user->assignRole('customer');

            CustomerProfile::query()->create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'] ?? null,
            ]);

            $plainToken = $this->issueActivationToken($user, ActivationToken::TYPE_CLIENT);
            $user->notify(new AccountActivationNotification($plainToken, ActivationToken::TYPE_CLIENT));

            $this->auditLogService->log('auth.customer_registered', $user, $user, [
                'email' => $user->email,
            ]);

            return $user;
        });

        return $user->fresh(['customerProfile']);
    }

    public function registerProvider(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $firstName = trim($data['first_name']);
            $lastName = trim($data['last_name']);
            $fullName = trim($firstName . ' ' . $lastName);
            $email = strtolower(trim($data['email']));

            $user = User::query()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => $fullName,
                'email' => $email,
                'mobile' => trim($data['mobile']),
                'user_type' => User::TYPE_PROVIDER,
                'status' => User::STATUS_PENDING_ACTIVATION,
                'password' => $data['password'],
            ]);

            $user->assignRole('provider_owner');

            $companyName = trim((string) ($data['company_name'] ?? ($fullName . ' Workspace')));
            $provider = Provider::query()->create([
                'owner_user_id' => $user->id,
                'company_name' => $companyName,
                'slug' => $this->uniqueProviderSlug($companyName),
                'brand_name' => $data['brand_name'] ?? $companyName,
                'business_email' => $email,
                'business_phone' => trim($data['mobile']),
                'city_id' => $data['city_id'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => Provider::STATUS_PENDING,
                'kyc_status' => Provider::KYC_NOT_SUBMITTED,
            ]);

            $ownerRole = ProviderRole::query()->create([
                'provider_id' => $provider->id,
                'workspace_id' => null,
                'name' => 'Provider Admin',
                'description' => 'Full provider organization owner role.',
                'permissions' => ProviderRole::CANONICAL_PERMISSIONS,
                'is_system' => true,
            ]);

            $providerUser = ProviderUser::query()->create([
                'provider_id' => $provider->id,
                'user_id' => $user->id,
                'workspace_id' => null,
                'provider_role_id' => $ownerRole->id,
                'role_name' => 'Provider Admin',
                'is_owner' => true,
                'status' => ProviderUser::STATUS_ACTIVE,
                'invited_by' => $user->id,
            ]);

            ProviderSetting::query()->create([
                'provider_id' => $provider->id,
                'notification_email' => $email,
            ]);

            $plainToken = $this->issueActivationToken($user, ActivationToken::TYPE_PROVIDER);
            $user->notify(new AccountActivationNotification($plainToken, ActivationToken::TYPE_PROVIDER));

            $this->auditLogService->log('auth.provider_registered', $user, $provider, [
                'provider_slug' => $provider->slug,
            ], $provider->id);

            return [
                'user' => $user,
                'provider' => $provider,
                'provider_user' => $providerUser,
            ];
        });
    }

    public function attemptLogin(string $identifier, string $password, ?string $expectedType = null): array
    {
        $user = $this->verifyPortalCredentials($identifier, $password, $expectedType, false);
        $token = $user->createToken($user->user_type . '-auth-token')->plainTextToken;

        $this->auditLogService->log('auth.login', $user, $user, [
            'user_type' => $user->user_type,
        ]);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function verifySessionLogin(
        string $identifier,
        string $password,
        string $expectedType,
        bool $allowRestrictedProviderPortal = false
    ): User {
        $user = $this->verifyPortalCredentials($identifier, $password, $expectedType, $allowRestrictedProviderPortal);

        $this->auditLogService->log('auth.web_login', $user, $user, [
            'user_type' => $user->user_type,
            'guard' => 'web',
        ]);

        return $user;
    }

    public function verifyPortalCredentials(
        string $identifier,
        string $password,
        ?string $expectedType = null,
        bool $allowRestrictedProviderPortal = false
    ): User {
        $clean = trim($identifier);
        $lower = strtolower($clean);

        /** @var User|null $user */
        $user = User::query()
            ->where(function ($query) use ($clean, $lower): void {
                $query->where('email', $lower)
                    ->orWhere('mobile', $clean)
                    ->orWhere('username', $clean);
            })
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['Invalid credentials provided.'],
            ]);
        }

        if ($expectedType !== null && $user->user_type !== $expectedType) {
            throw new HttpException(403, 'Account cannot access this portal.');
        }

        if ($user->status === User::STATUS_PENDING_ACTIVATION || $user->email_verified_at === null) {
            throw new HttpException(403, 'Account is not activated. Please verify your email first.');
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            throw new HttpException(403, 'Account is inactive or suspended.');
        }

        if ($user->user_type === User::TYPE_SUPER_ADMIN && !$user->hasRole('super_admin')) {
            throw new HttpException(403, 'Super Admin role is required.');
        }

        if ($user->user_type === User::TYPE_PROVIDER) {
            $membershipQuery = $user->providerMemberships()
                ->where('status', ProviderUser::STATUS_ACTIVE);

            if ($allowRestrictedProviderPortal) {
                $membershipQuery->whereHas('provider');
            } else {
                $membershipQuery->whereHas('provider', fn($q) => $q->whereNotIn('status', [Provider::STATUS_SUSPENDED, Provider::STATUS_REJECTED]));
            }

            if (!$membershipQuery->exists()) {
                throw new HttpException(403, 'No active provider access associated with this account.');
            }
        }

        return $user;
    }

    public function activateByToken(string $plainToken, ?string $expectedType = null): User
    {
        $hash = hash('sha256', $plainToken);

        $record = ActivationToken::query()
            ->where('token_hash', $hash)
            ->when($expectedType !== null, fn($q) => $q->where('type', $expectedType))
            ->first();

        if (!$record || $record->used_at !== null || $record->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'token' => ['Activation token is invalid or has expired.'],
            ]);
        }

        return DB::transaction(function () use ($record): User {
            $user = $record->user()->lockForUpdate()->firstOrFail();

            $user->forceFill([
                'email_verified_at' => $user->email_verified_at ?? now(),
                'status' => User::STATUS_ACTIVE,
            ])->save();

            $record->forceFill(['used_at' => now()])->save();

            $this->auditLogService->log('auth.account_activated', $user, $user, [
                'activation_type' => $record->type,
            ]);

            return $user;
        });
    }

    public function resendActivation(string $email, string $type): void
    {
        $user = User::query()
            ->where('email', strtolower(trim($email)))
            ->first();

        if (!$user || $user->status !== User::STATUS_PENDING_ACTIVATION) {
            return;
        }

        $plainToken = $this->issueActivationToken($user, $type);
        $user->notify(new AccountActivationNotification($plainToken, $type));
    }

    public function sendOtp(string $identifier, string $purpose = 'login'): void
    {
        $clean = trim($identifier);
        $channel = filter_var($clean, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';

        $user = User::query()
            ->where($channel === 'email' ? 'email' : 'mobile', $channel === 'email' ? strtolower($clean) : $clean)
            ->first();

        if (!$user || !$user->isActive()) {
            return;
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $ttl = (int) config('spaceworq.otp.ttl_minutes', 10);

        OtpVerification::query()->create([
            'user_id' => $user->id,
            'channel' => $channel,
            'identifier' => $channel === 'email' ? strtolower($clean) : $clean,
            'otp_hash' => Hash::make($otp),
            'purpose' => $purpose,
            'attempts' => 0,
            'expires_at' => now()->addMinutes($ttl),
        ]);

        $user->notify(new OtpCodeNotification($otp, $ttl));
    }

    public function verifyOtp(string $identifier, string $otpCode, string $purpose = 'login'): array
    {
        $user = $this->verifyOtpUser($identifier, $otpCode, $purpose);
        $token = $user->createToken('customer-otp-token')->plainTextToken;

        $this->auditLogService->log('auth.otp_login', $user, $user);

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function verifyOtpSession(string $identifier, string $otpCode, string $purpose = 'login'): User
    {
        $user = $this->verifyOtpUser($identifier, $otpCode, $purpose);

        $this->auditLogService->log('auth.web_otp_login', $user, $user, [
            'guard' => 'web',
        ]);

        return $user;
    }

    private function verifyOtpUser(string $identifier, string $otpCode, string $purpose = 'login'): User
    {
        $clean = trim($identifier);
        $normalized = filter_var($clean, FILTER_VALIDATE_EMAIL) ? strtolower($clean) : $clean;
        $maxAttempts = (int) config('spaceworq.otp.max_attempts', 5);

        $record = OtpVerification::query()
            ->where('identifier', $normalized)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (!$record || $record->expires_at->isPast() || $record->attempts >= $maxAttempts) {
            throw ValidationException::withMessages([
                'otp' => ['OTP is invalid, expired, or maximum attempts exceeded.'],
            ]);
        }

        if (!Hash::check($otpCode, $record->otp_hash)) {
            $record->increment('attempts');
            throw ValidationException::withMessages([
                'otp' => ['Invalid verification code.'],
            ]);
        }

        $user = $record->user;
        if (!$user || !$user->isActive() || $user->user_type !== User::TYPE_CUSTOMER) {
            throw new HttpException(403, 'OTP login is unavailable for this account.');
        }

        $record->forceFill(['verified_at' => now()])->save();

        return $user;
    }

    private function issueActivationToken(User $user, string $type): string
    {
        $plain = Str::random(64);

        ActivationToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plain),
            'type' => $type,
            'expires_at' => now()->addHours(24),
        ]);

        return $plain;
    }

    private function uniqueProviderSlug(string $companyName): string
    {
        $base = Str::slug($companyName) ?: 'provider';
        $slug = $base;
        $counter = 1;

        while (Provider::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
