<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin',
            'secretary',
            'finance_officer',
            'society_leader',
            'parishioner',
        ];

        foreach ($roles as $role) {
            $user = User::firstOrCreate(
                [
                    'email' => "{$role}@stferdinand.com",
                ],
                [
                    'name' => ucwords(str_replace('_', ' ', $role)),
                    'password' => Hash::make('Password@123'),
                    'is_active' => true,
                    'must_change_password' => true,
                ]
            );

            // Assign role (avoid duplicate assignment)
            if (!$user->hasRole($role)) {
                $user->assignRole($role);
            }
        }
    }
}