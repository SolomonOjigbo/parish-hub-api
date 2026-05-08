<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'members.view', 'members.create', 'members.edit', 'members.delete', 'members.export',
            'families.view', 'families.create', 'families.edit', 'families.delete',
            'societies.view', 'societies.create', 'societies.edit', 'societies.delete', 'societies.manage_members',
            'events.view', 'events.create', 'events.edit', 'events.delete', 'events.mark_attendance',
            'finance.view', 'finance.create', 'finance.edit', 'finance.delete', 'finance.export',
            'communications.view', 'communications.send',
            'reports.view', 'reports.export',
            'staff.view', 'staff.create', 'staff.edit', 'staff.delete',
            'committees.view', 'committees.create', 'committees.edit', 'committees.delete',
            'settings.view', 'settings.manage',
            'roles.view', 'roles.manage',
            'audit.view',
            'sync.push', 'sync.pull',
            'portal.access',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $financeOfficer = Role::firstOrCreate(['name' => 'finance_officer', 'guard_name' => 'web']);
        $financeOfficer->syncPermissions([
            'finance.view', 'finance.create', 'finance.edit', 'finance.export',
            'reports.view', 'reports.export',
            'members.view',
            'portal.access',
        ]);

        $secretary = Role::firstOrCreate(['name' => 'secretary', 'guard_name' => 'web']);
        $secretary->syncPermissions([
            'members.view', 'members.create', 'members.edit', 'members.export',
            'families.view', 'families.create', 'families.edit',
            'societies.view',
            'events.view', 'events.create', 'events.edit', 'events.mark_attendance',
            'communications.view', 'communications.send',
            'reports.view',
            'portal.access',
        ]);

        $societyLeader = Role::firstOrCreate(['name' => 'society_leader', 'guard_name' => 'web']);
        $societyLeader->syncPermissions([
            'societies.view', 'societies.manage_members',
            'members.view',
            'events.view', 'events.mark_attendance',
            'communications.send',
            'portal.access',
        ]);

        $parishioner = Role::firstOrCreate(['name' => 'parishioner', 'guard_name' => 'web']);
        $parishioner->syncPermissions([
            'portal.access',
            'events.view',
            'members.view',
        ]);
    }
}
