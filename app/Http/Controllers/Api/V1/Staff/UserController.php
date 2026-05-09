<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class UserController extends BaseApiController
{
    /**
     * Store a newly created user account (no staff profile).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        return $this->success($user, 'User account created successfully', 201);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $user->id],
        ]);

        $user->update($request->only(['name', 'email']));

        return $this->success($user, 'User updated successfully');
    }

    /**
     * Deactivate the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->update(['is_active' => false]);
        $user->tokens()->delete();

        return $this->success(null, 'User deactivated successfully');
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'exists:roles,name'],
        ]);

        $user->syncRoles([$request->role]);

        return $this->success([
            'user_id' => $user->id,
            'roles' => $user->roles->pluck('name'),
        ], 'Role assigned successfully');
    }

    /**
     * Trigger password reset email for user.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(null, 'Password reset link sent successfully');
        }

        return $this->error('Failed to send password reset link', 500);
    }
}
