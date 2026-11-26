<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\AuditLogService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminProvisioningService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    /**
     * Validate inputs, guard against account takeover or non-admin collisions,
     * and create (or intentionally update) a verified Super Admin account.
     *
     * @return array{user: User, action: string}
     */
    public function provision(array $input, bool $updateExisting = false, string $source = 'cli'): array
    {
        $normalized = [
            'name' => trim((string) ($input['name'] ?? '')),
            'email' => strtolower(trim((string) ($input['email'] ?? ''))),
            'mobile' => isset($input['mobile']) && trim((string) $input['mobile']) !== ''
                ? trim((string) $input['mobile'])
                : null,
            'username' => isset($input['username']) && trim((string) $input['username']) !== ''
                ? trim((string) $input['username'])
                : null,
            'password' => (string) ($input['password'] ?? ''),
        ];

        Validator::make($normalized, [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:180'],
            'mobile' => ['nullable', 'string', 'regex:/^[\d\s\-()+]{7,20}$/', 'max:30'],
            'username' => ['nullable', 'string', 'min:3', 'max:100', 'alpha_dash'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols(), 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($normalized, $updateExisting, $source): array {
            $candidates = User::withTrashed()
                ->where(function ($query) use ($normalized): void {
                    $query->where('email', $normalized['email']);
                    if ($normalized['mobile'] !== null) {
                        $query->orWhere('mobile', $normalized['mobile']);
                    }
                    if ($normalized['username'] !== null) {
                        $query->orWhere('username', $normalized['username']);
                    }
                })
                ->lockForUpdate()
                ->get();

            foreach ($candidates as $candidate) {
                if ($candidate->trashed() || $candidate->user_type !== User::TYPE_SUPER_ADMIN) {
                    throw ValidationException::withMessages([
                        'email' => ['Refusing to provision: email, mobile, or username belongs to an existing customer, provider, or deactivated account.'],
                    ]);
                }
            }

            /** @var User|null $existingByEmail */
            $existingByEmail = $candidates->firstWhere('email', $normalized['email']);

            foreach ($candidates as $candidate) {
                if (! $existingByEmail || $candidate->id !== $existingByEmail->id) {
                    throw ValidationException::withMessages([
                        'mobile' => ['Provided mobile number or username is already assigned to another account.'],
                    ]);
                }
            }

            if ($existingByEmail !== null && ! $updateExisting) {
                throw ValidationException::withMessages([
                    'email' => ['A Super Admin account already exists for this email. Use --update-existing to update credentials or details.'],
                ]);
            }

            $this->ensureSuperAdminRoleExists();
            [$firstName, $lastName] = $this->splitName($normalized['name']);

            if ($existingByEmail !== null) {
                $existingByEmail->forceFill([
                    'first_name' => $firstName,
                    'last_name' => $lastName !== '' ? $lastName : $existingByEmail->last_name,
                    'name' => $normalized['name'],
                    'mobile' => $normalized['mobile'] ?? $existingByEmail->mobile,
                    'username' => $normalized['username'] ?? $existingByEmail->username,
                    'status' => User::STATUS_ACTIVE,
                    'email_verified_at' => $existingByEmail->email_verified_at ?? now(),
                    'password' => $normalized['password'],
                ])->save();

                $existingByEmail->syncRoles(['super_admin']);
                $existingByEmail->tokens()->delete();

                $this->auditLogService->log('admin.super_admin_updated', $existingByEmail, $existingByEmail, [
                    'source' => $source,
                    'email' => $existingByEmail->email,
                ]);

                return [
                    'user' => $existingByEmail->fresh(),
                    'action' => 'updated',
                ];
            }

            $admin = User::query()->create([
                'first_name' => $firstName,
                'last_name' => $lastName !== '' ? $lastName : null,
                'name' => $normalized['name'],
                'email' => $normalized['email'],
                'mobile' => $normalized['mobile'],
                'username' => $normalized['username'],
                'user_type' => User::TYPE_SUPER_ADMIN,
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'password' => $normalized['password'],
            ]);

            $admin->syncRoles(['super_admin']);

            $this->auditLogService->log('admin.super_admin_provisioned', $admin, $admin, [
                'source' => $source,
                'email' => $admin->email,
            ]);

            return [
                'user' => $admin->fresh(),
                'action' => 'created',
            ];
        });
    }

    private function ensureSuperAdminRoleExists(): void
    {
        if (! Role::query()->where('name', 'super_admin')->where('guard_name', 'web')->exists()) {
            (new RolePermissionSeeder())->run();
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);

        return [
            $parts[0] ?? $fullName,
            $parts[1] ?? '',
        ];
    }
}
