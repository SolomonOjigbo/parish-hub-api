<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Resources\Api\V1\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends BaseApiController
{
    /**
     * Authenticate a user and return a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Invalid credentials', 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->is_active) {
            Auth::logout();

            return $this->error('Account disabled. Contact admin.', 403);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('parishhub-app')->plainTextToken;

        $user->load('roles', 'permissions');

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Login successful');
    }

    /**
     * Revoke the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Return the authenticated user with roles and permissions.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('roles', 'permissions');

        return $this->success(new UserResource($user));
    }

    /**
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect', 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        $user->tokens()->delete();

        $token = $user->createToken('parishhub-app')->plainTextToken;

        $user->load('roles', 'permissions');

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'Password changed successfully');
    }

    /**
     * Send a password reset link to the given email.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return $this->success(null, 'If this email exists, a reset link has been sent.');
    }

    /**
     * Reset the user's password using a valid token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_change_password' => false,
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(__($status), 422);
        }

        return $this->success(null, __($status));
    }
}
