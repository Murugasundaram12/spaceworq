<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Models\ProviderRole;
use App\Models\ProviderSetting;
use App\Models\ProviderUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateLocalVendorTestAccountsCommand extends Command
{
    protected $signature = 'spaceworq:create-test-vendors {--json : Output result in JSON format}';

    protected $description = 'Create isolated local-only vendor test accounts for Phase 4 browser verification';

    public function handle(): int
    {
        // 1. Mandatory safety checks
        if (config('app.env') !== 'local') {
            $this->error('Aborting: This command is restricted to local development environments (APP_ENV=local).');
            return self::FAILURE;
        }

        $defaultConn = config('database.default');
        $dbName = (string) config("database.connections.{$defaultConn}.database");

        if (str_contains(strtolower($dbName), 'spaceworq_db')
            || str_contains(strtolower($dbName), 'production')
            || str_contains(strtolower($dbName), 'staging')
        ) {
            $this->error("Aborting: Unsafe database detected ({$dbName}). Refusing to run against spaceworq_db or production/staging.");
            return self::FAILURE;
        }

        if ($defaultConn !== 'sqlite' && !in_array(config("database.connections.{$defaultConn}.host"), ['127.0.0.1', 'localhost', null], true)) {
            $this->error('Aborting: Unsafe remote database host detected.');
            return self::FAILURE;
        }

        $pendingEmail = 'qa.pending@spaceworq.local';
        $approvedEmail = 'qa.approved@spaceworq.local';

        $results = [];

        // 2. Account A: Pending Vendor
        $existingUserA = User::query()->where('email', $pendingEmail)->first();
        if ($existingUserA) {
            $providerA = Provider::query()->where('owner_user_id', $existingUserA->id)->first();
            $results['pending'] = [
                'status' => 'already_exists',
                'user_id' => $existingUserA->id,
                'email' => $existingUserA->email,
                'user_status' => $existingUserA->status,
                'provider_id' => $providerA?->id,
                'provider_status' => $providerA?->status,
                'kyc_status' => $providerA?->kyc_status,
                'password' => '(preserved existing password)',
            ];
            $this->info("Pending vendor account {$pendingEmail} already exists.");
        } else {
            $passwordA = 'Vp#' . Str::random(12) . '!9';
            $createdA = DB::transaction(function () use ($pendingEmail, $passwordA) {
                $user = User::query()->create([
                    'first_name' => 'QA',
                    'last_name' => 'Pending',
                    'name' => 'QA Pending',
                    'email' => $pendingEmail,
                    'mobile' => '9876543211',
                    'username' => 'qa.pending',
                    'user_type' => User::TYPE_PROVIDER,
                    'status' => User::STATUS_ACTIVE,
                    'email_verified_at' => now(),
                    'password' => Hash::make($passwordA),
                ]);

                $user->assignRole('provider_owner');

                $provider = Provider::query()->create([
                    'owner_user_id' => $user->id,
                    'company_name' => 'QA Pending Workspaces',
                    'slug' => 'qa-pending-workspaces',
                    'brand_name' => 'QA Pending Hub',
                    'business_email' => $pendingEmail,
                    'business_phone' => '9876543211',
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

                ProviderUser::query()->create([
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
                    'notification_email' => $pendingEmail,
                ]);

                return ['user' => $user, 'provider' => $provider];
            });

            $results['pending'] = [
                'status' => 'created',
                'user_id' => $createdA['user']->id,
                'email' => $createdA['user']->email,
                'user_status' => $createdA['user']->status,
                'provider_id' => $createdA['provider']->id,
                'provider_status' => $createdA['provider']->status,
                'kyc_status' => $createdA['provider']->kyc_status,
                'password' => $passwordA,
            ];
            $this->info("Created pending vendor account {$pendingEmail}.");
        }

        // 3. Account B: Approved Vendor
        $existingUserB = User::query()->where('email', $approvedEmail)->first();
        if ($existingUserB) {
            $providerB = Provider::query()->where('owner_user_id', $existingUserB->id)->first();
            $results['approved'] = [
                'status' => 'already_exists',
                'user_id' => $existingUserB->id,
                'email' => $existingUserB->email,
                'user_status' => $existingUserB->status,
                'provider_id' => $providerB?->id,
                'provider_status' => $providerB?->status,
                'kyc_status' => $providerB?->kyc_status,
                'password' => '(preserved existing password)',
            ];
            $this->info("Approved vendor account {$approvedEmail} already exists.");
        } else {
            $passwordB = 'Va#' . Str::random(12) . '!8';
            $createdB = DB::transaction(function () use ($approvedEmail, $passwordB) {
                $user = User::query()->create([
                    'first_name' => 'QA',
                    'last_name' => 'Approved',
                    'name' => 'QA Approved',
                    'email' => $approvedEmail,
                    'mobile' => '9876543212',
                    'username' => 'qa.approved',
                    'user_type' => User::TYPE_PROVIDER,
                    'status' => User::STATUS_ACTIVE,
                    'email_verified_at' => now(),
                    'password' => Hash::make($passwordB),
                ]);

                $user->assignRole('provider_owner');

                $provider = Provider::query()->create([
                    'owner_user_id' => $user->id,
                    'company_name' => 'QA Approved Workspaces',
                    'slug' => 'qa-approved-workspaces',
                    'brand_name' => 'QA Approved Hub',
                    'business_email' => $approvedEmail,
                    'business_phone' => '9876543212',
                    'status' => Provider::STATUS_ACTIVE,
                    'kyc_status' => Provider::KYC_VERIFIED,
                    'verified_at' => now()->subDays(2),
                ]);

                $ownerRole = ProviderRole::query()->create([
                    'provider_id' => $provider->id,
                    'workspace_id' => null,
                    'name' => 'Provider Admin',
                    'description' => 'Full provider organization owner role.',
                    'permissions' => ProviderRole::CANONICAL_PERMISSIONS,
                    'is_system' => true,
                ]);

                ProviderUser::query()->create([
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
                    'notification_email' => $approvedEmail,
                ]);

                return ['user' => $user, 'provider' => $provider];
            });

            $results['approved'] = [
                'status' => 'created',
                'user_id' => $createdB['user']->id,
                'email' => $createdB['user']->email,
                'user_status' => $createdB['user']->status,
                'provider_id' => $createdB['provider']->id,
                'provider_status' => $createdB['provider']->status,
                'kyc_status' => $createdB['provider']->kyc_status,
                'password' => $passwordB,
            ];
            $this->info("Created approved vendor account {$approvedEmail}.");
        }

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
        }

        return self::SUCCESS;
    }
}
