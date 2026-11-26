<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Admin\SuperAdminProvisioningService;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $config = config('spaceworq.super_admin', []);
        $email = isset($config['email']) ? strtolower(trim((string) $config['email'])) : '';
        $password = (string) ($config['password'] ?? '');

        if ($email === '' || $password === '') {
            $this->command?->info('SuperAdminSeeder skipped: SPACEWORQ_SUPER_ADMIN_EMAIL / SPACEWORQ_SUPER_ADMIN_PASSWORD not set.');

            return;
        }

        $existing = User::withTrashed()->where('email', $email)->first();
        if ($existing !== null) {
            if ($existing->user_type === User::TYPE_SUPER_ADMIN && ! $existing->trashed()) {
                if (! $existing->hasRole('super_admin')) {
                    $existing->assignRole('super_admin');
                }
                $this->command?->info("Super Admin already provisioned for {$email}; skipping.");

                return;
            }
        }

        app(SuperAdminProvisioningService::class)->provision([
            'name' => $config['name'] ?: 'SpaceWorq Super Admin',
            'email' => $email,
            'mobile' => $config['mobile'] ?? null,
            'username' => $config['username'] ?? null,
            'password' => $password,
        ], false, 'seeder');

        $this->command?->info("Super Admin provisioned for {$email}.");
    }
}
