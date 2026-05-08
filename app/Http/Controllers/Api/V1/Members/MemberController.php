<?php

namespace App\Http\Controllers\Api\V1\Members;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MemberController extends BaseApiController
{
    // GET /api/v1/members
    public function index(Request $request): JsonResponse
    {
        // TODO: paginated, filtered member list
        return $this->error('Not implemented yet', 501);
    }

    // POST /api/v1/members
    public function store(Request $request): JsonResponse
    {
        // TODO: validate and create member
        return $this->error('Not implemented yet', 501);
    }

    // GET /api/v1/members/{id}
    public function show(int $id): JsonResponse
    {
        // TODO: return full member profile
        return $this->error('Not implemented yet', 501);
    }

    // PUT /api/v1/members/{id}
    public function update(Request $request, int $id): JsonResponse
    {
        // TODO: validate and update member
        return $this->error('Not implemented yet', 501);
    }

    // DELETE /api/v1/members/{id}
    public function destroy(int $id): JsonResponse
    {
        // TODO: soft delete member
        return $this->error('Not implemented yet', 501);
    }
}
