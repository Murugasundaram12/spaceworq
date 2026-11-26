<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            MasterDataSeeder::class,
        ]);

        if (config('spaceworq.super_admin.seed_on_db_seed', false)
            && ! empty(config('spaceworq.super_admin.email'))
            && ! empty(config('spaceworq.super_admin.password'))) {
            $this->call(SuperAdminSeeder::class);
        }
    }
}
