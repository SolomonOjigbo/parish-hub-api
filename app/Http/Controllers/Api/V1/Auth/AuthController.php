<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends BaseApiController
{
    // POST /api/v1/auth/login
    public function login(Request $request): JsonResponse
    {
        // TODO: implement login logic
        return $this->error('Not implemented yet', 501);
    }

    // POST /api/v1/auth/logout
    public function logout(Request $request): JsonResponse
    {
        // TODO: implement logout — revoke current token
        return $this->error('Not implemented yet', 501);
    }

    // GET /api/v1/auth/me
    public function me(Request $request): JsonResponse
    {
        // TODO: return authenticated user with roles and permissions
        return $this->error('Not implemented yet', 501);
    }

    // POST /api/v1/auth/forgot-password
    public function forgotPassword(Request $request): JsonResponse
    {
        // TODO: send password reset link
        return $this->error('Not implemented yet', 501);
    }

    // POST /api/v1/auth/reset-password
    public function resetPassword(Request $request): JsonResponse
    {
        // TODO: validate token and reset password
        return $this->error('Not implemented yet', 501);
    }
}
