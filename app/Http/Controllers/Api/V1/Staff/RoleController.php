<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends BaseApiController
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('roles.view');

        $roles = Role::with('permissions')->get();

        $rolesWithCounts = $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions_count' => $role->permissions->count(),
                'users_count' => $role->users()->count(),
                'permissions' => $role->permissions->pluck('name'),
            ];
        });

        return $this->success($rolesWithCounts);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('roles.manage');

        $request->validate([
            'name' => ['required', 'string', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ], 'Role created successfully', 201);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        $this->authorize('roles.manage');

        $request->validate([
            'name' => ['sometimes', 'string', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ]);

        if ($request->has('name')) {
            $role->update(['name' => $request->name]);
        }

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name'),
        ], 'Role updated successfully');
    }

    /**
     * Display all permissions grouped by module.
     */
    public function permissions(): JsonResponse
    {
        $this->authorize('roles.view');

        $allPermissions = Permission::all();

        $grouped = $allPermissions->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        })->map(function ($group) {
            return $group->pluck('name')->sort()->values();
        });

        return $this->success($grouped);
    }
}
