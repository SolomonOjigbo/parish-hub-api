<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // All roles in one place (clean + scalable)
        $roles = [
            'super_admin',
            'secretary',
            'finance_officer',
            'society_leader',
            'parishioner',
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate([
                'name' => $role,
                'guard_name' => 'api',
            ]);
        }

        /* ================= ADMIN ROLES ================= */
        // $adminRoles = ['super_admin', 'secretary', 'finance_officer'];

        // foreach ($adminRoles as $role) {
        //     Role::updateOrCreate(
        //         ['name' => $role, 'guard_name' => 'api']
        //     );
        // }

        /* ================= SOCIETY LEADER ROLES ================= */
        // $societyLeaderRoles = [
        //     'society_leader',

        // ];

        // foreach ($societyLeaderRoles as $role) {
        //     Role::updateOrCreate(
        //         ['name' => $role, 'guard_name' => 'api']
        //     );
        // }

        /* ================= USER ROLES ================= */
        // $userRoles = [
        //     'parishioner',

        // ];

        // foreach ($userRoles as $role) {
        //     Role::updateOrCreate(
        //         ['name' => $role, 'guard_name' => 'api']
        //     );
        // }
    }
}
