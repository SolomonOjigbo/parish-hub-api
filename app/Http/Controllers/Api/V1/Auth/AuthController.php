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
use Tymon\JWTAuth\Facades\JWTAuth;


class AuthController extends BaseApiController
{
    /**
     * Authenticate a user and return a Sanctum token.
     */
 public function login(LoginRequest $request): JsonResponse
{
    $credentials = $request->only('email', 'password');

    if (!$token = JWTAuth::attempt($credentials)) {
        return $this->error('Invalid credentials', 401);
    }

    $user = JWTAuth::user();

    if (!$user->is_active) {
        JWTAuth::invalidate($token);
        return $this->error('Account disabled. Contact admin.', 403);
    }

    $user->update(['last_login_at' => now()]);

    return $this->success([
        'token' => $token,
        'user' => new UserResource($user->load('roles')),
    ], 'Login successful');
}
    

    /**
     * Revoke the current access token.
     */
 
    public function logout(): JsonResponse
{
    JWTAuth::invalidate(JWTAuth::getToken());

    return $this->success(null, 'Logged out successfully');
}

    /**
     * Return the authenticated user with roles and permissions.
     */
   
    public function me(): JsonResponse
{
    $user = auth()->user()->load('roles', 'permissions');

    return $this->success(new UserResource($user));
}

    /**
     * Change the authenticated user's password.
     */

    public function changePassword(Request $request): JsonResponse
{
    $request->validate([
        'current_password' => ['required'],
        'password' => ['required', 'min:8', 'confirmed'],
    ]);

    $user = auth()->user();

    if (!Hash::check($request->current_password, $user->password)) {
        return $this->error('Current password is incorrect', 422);
    }

    $user->update([
        'password' => Hash::make($request->password),
        'must_change_password' => false,
    ]);

    $token = JWTAuth::fromUser($user);

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
    $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status !== Password::RESET_LINK_SENT) {
        return $this->error(__($status), 422);
    }

    return $this->success(
        null,
        'If this email exists, a reset link has been sent.'
    );
}

    /**
     * Reset the user's password using a valid token.
     */

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
{
    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function (User $user, string $password) {

            // update password
            $user->forceFill([
                'password' => Hash::make($password),
                'must_change_password' => false,
            ])->save();

            // ❌ REMOVE SANCTUM LOGIC
            // $user->tokens()->delete();

            // OPTIONAL: invalidate JWT sessions if user already logged in
            JWTAuth::invalidate(JWTAuth::getToken());
        }
    );

    if ($status !== Password::PASSWORD_RESET) {
        return $this->error(__($status), 422);
    }

    return $this->success(
        null,
        'Password has been reset successfully.'
    );
}
}
