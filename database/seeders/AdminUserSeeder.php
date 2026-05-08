<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@stferdinand.com'],
            [
                'name' => 'Parish Administrator',
                'password' => Hash::make('Admin@1234'),
                'is_active' => true,
                'must_change_password' => true,
            ]
        );

        $admin->assignRole('super_admin');
    }
}
