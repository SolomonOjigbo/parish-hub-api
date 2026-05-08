<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ZoneSeeder::class,
            SocietySeeder::class,
            AdminUserSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
