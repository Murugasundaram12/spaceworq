<?php

namespace App\Console\Commands;

use App\Services\Admin\SuperAdminProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class CreateSuperAdminCommand extends Command
{
    /**
     * Never accept a password CLI flag so credentials are not leaked in shell history or process lists.
     */
    protected $signature = 'spaceworq:create-super-admin
        {--name= : Full name of the Super Admin}
        {--email= : Email address of the Super Admin}
        {--mobile= : Optional unique mobile number}
        {--username= : Optional unique username}
        {--from-env : Read credentials from SPACEWORQ_SUPER_ADMIN_* environment variables}
        {--update-existing : Update an existing Super Admin account for the given email}';

    protected $description = 'Provision a verified SpaceWorq Super Admin account securely via hidden prompt or env configuration';

    public function handle(SuperAdminProvisioningService $provisioningService): int
    {
        $fromEnv = (bool) $this->option('from-env');
        $updateExisting = (bool) $this->option('update-existing');
        $envConfig = config('spaceworq.super_admin', []);

        if ($fromEnv) {
            $name = $this->option('name') ?: ($envConfig['name'] ?? 'SpaceWorq Super Admin');
            $email = $this->option('email') ?: ($envConfig['email'] ?? null);
            $mobile = $this->option('mobile') ?: ($envConfig['mobile'] ?? null);
            $username = $this->option('username') ?: ($envConfig['username'] ?? null);
            $password = $envConfig['password'] ?? null;

            if (empty($email) || empty($password)) {
                $this->error('SPACEWORQ_SUPER_ADMIN_EMAIL and SPACEWORQ_SUPER_ADMIN_PASSWORD must be configured when using --from-env.');

                return self::FAILURE;
            }
        } else {
            if (! $this->input->isInteractive()) {
                $this->error('Interactive terminal required unless --from-env is supplied with configured environment credentials.');

                return self::FAILURE;
            }

            $name = $this->option('name') ?: $this->ask('Super Admin full name');
            $email = $this->option('email') ?: $this->ask('Super Admin email address');
            $mobile = $this->option('mobile');
            $username = $this->option('username');

            $password = (string) $this->secret('Super Admin password (min 8 chars, upper, lower, number, symbol)');
            $confirmation = (string) $this->secret('Confirm Super Admin password');

            if ($password === '' || $password !== $confirmation) {
                $this->error('Password confirmation did not match or was empty.');

                return self::FAILURE;
            }
        }

        try {
            $result = $provisioningService->provision([
                'name' => $name,
                'email' => $email,
                'mobile' => $mobile,
                'username' => $username,
                'password' => $password,
            ], $updateExisting, $fromEnv ? 'artisan_env' : 'artisan_interactive');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $verb = $result['action'] === 'updated' ? 'updated' : 'created';
        $this->info("Super Admin {$verb} successfully: {$result['user']->email} (ID: {$result['user']->id}).");

        return self::SUCCESS;
    }
}
