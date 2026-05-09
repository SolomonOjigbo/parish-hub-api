<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\StaffProfile;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Resources\Api\V1\StaffResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role as SpatieRole;

class StaffController extends BaseApiController
{
    /**
     * Display a listing of staff profiles.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('staff.view');

        $query = StaffProfile::with(['user', 'user.roles', 'member']);

        if ($request->has('role')) {
            $query->whereHas('user.roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $staff = $query->paginate($request->input('per_page', 15));

        return $this->paginated(
            StaffResource::collection($staff),
            paginator_meta($staff),
        );
    }

    /**
     * Store a newly created staff profile.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('staff.create');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'exists:roles,name'],
            'member_id' => ['nullable', 'exists:members,id'],
            'job_title' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'in:full_time,part_time,contract,volunteer'],
            'start_date' => ['nullable', 'date'],
        ]);

        $staff = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $user->assignRole($request->role);

            $profile = StaffProfile::create([
                'user_id' => $user->id,
                'member_id' => $request->member_id,
                'job_title' => $request->job_title,
                'employment_type' => $request->employment_type,
                'start_date' => $request->start_date,
            ]);

            // Send welcome email
            try {
                Mail::raw("Welcome to St. Ferdinand Catholic Church!\n\nYour account has been created.\nEmail: {$user->email}\nPassword: {$request->password}\n\nPlease change your password after your first login.", function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Welcome to St. Ferdinand Parish - Staff Account');
                });
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email', ['error' => $e->getMessage()]);
            }

            return $profile;
        });

        return $this->success(new StaffResource($staff->load(['user', 'user.roles', 'member'])), 'Staff profile created successfully', 201);
    }

    /**
     * Display the specified staff profile.
     */
    public function show(StaffProfile $staff): JsonResponse
    {
        $this->authorize('staff.view');

        $staff->load(['user', 'user.roles', 'user.permissions', 'member']);

        $allPermissions = Permission::all()->pluck('name');
        $userPermissions = $staff->user->getAllPermissions()->pluck('name');

        $permissionsMatrix = $allPermissions->map(function ($permission) use ($userPermissions) {
            return [
                'permission' => $permission,
                'has_permission' => $userPermissions->contains($permission),
            ];
        });

        return $this->success([
            'staff' => new StaffResource($staff),
            'permissions_matrix' => $permissionsMatrix,
        ]);
    }

    /**
     * Update the specified staff profile.
     */
    public function update(Request $request, StaffProfile $staff): JsonResponse
    {
        $this->authorize('staff.edit');

        $request->validate([
            'role' => ['sometimes', 'exists:roles,name'],
            'job_title' => ['sometimes', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'in:full_time,part_time,contract,volunteer'],
            'start_date' => ['sometimes', 'date'],
        ]);

        DB::transaction(function () use ($request, $staff) {
            if ($request->has('role')) {
                $staff->user->syncRoles([$request->role]);
            }

            $staff->update($request->only(['job_title', 'employment_type', 'start_date']));
        });

        return $this->success(new StaffResource($staff->load(['user', 'user.roles', 'member'])), 'Staff profile updated successfully');
    }

    /**
     * Remove the specified staff profile (deactivate).
     */
    public function destroy(StaffProfile $staff): JsonResponse
    {
        $this->authorize('staff.delete');

        DB::transaction(function () use ($staff) {
            $staff->user->update(['is_active' => false]);
            $staff->user->tokens()->delete();
        });

        return $this->success(null, 'Staff profile deactivated successfully');
    }
}
