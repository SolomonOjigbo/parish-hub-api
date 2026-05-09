<?php

namespace App\Http\Controllers\Api\V1\Societies;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Societies\StoreSocietyMemberRequest;
use App\Http\Requests\Api\V1\Societies\UpdateSocietyMemberRequest;
use App\Models\Society;
use App\Models\SocietyDue;
use App\Models\SocietyMember;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SocietyMemberController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:societies.view',   only: ['index']),
            new Middleware('permission:societies.edit',   only: ['store', 'update', 'destroy']),
        ];
    }

    /**
     * GET /api/v1/societies/{id}/members
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $society = Society::findOrFail($id);
        $perPage = (int) $request->query('per_page', 25);
        $year    = (int) $request->query('year', now()->year);

        $paginator = $society->members()
            ->with('contactDetail')
            ->paginate($perPage);

        $duesPaid = SocietyDue::where('society_id', $society->id)
            ->where('period_year', $year)
            ->select('member_id')
            ->groupBy('member_id')
            ->havingRaw('COUNT(*) >= 12')
            ->pluck('member_id')
            ->all();

        $items = $paginator->getCollection()->map(function ($member) use ($duesPaid): array {
            return [
                'id'                => $member->id,
                'membership_number' => $member->membership_number,
                'full_name'         => $member->full_name,
                'phone'             => $member->contactDetail?->primary_phone,
                'email'             => $member->contactDetail?->email,
                'role'              => $member->pivot->role,
                'joined_at'         => $member->pivot->joined_at,
                'is_active'         => (bool) $member->pivot->is_active,
                'dues_status'       => in_array($member->id, $duesPaid, true) ? 'paid' : 'pending',
            ];
        });

        return $this->paginated(
            $items,
            [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'Society members retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/societies/{id}/members
     */
    public function store(StoreSocietyMemberRequest $request, int $id): JsonResponse
    {
        $society = Society::findOrFail($id);
        $data    = $request->validated();

        $exists = SocietyMember::where('society_id', $society->id)
            ->where('member_id', $data['member_id'])
            ->exists();

        if ($exists) {
            return $this->error('Member already belongs to this society.', 422);
        }

        $pivot = SocietyMember::create([
            'society_id' => $society->id,
            'member_id'  => $data['member_id'],
            'role'       => $data['role']      ?? 'member',
            'joined_at'  => $data['joined_at'] ?? now()->toDateString(),
            'is_active'  => $data['is_active'] ?? true,
        ]);

        return $this->success($pivot, 'Member added to society.', Response::HTTP_CREATED);
    }

    /**
     * PUT /api/v1/societies/{id}/members/{memberId}
     */
    public function update(UpdateSocietyMemberRequest $request, int $id, int $memberId): JsonResponse
    {
        $pivot = SocietyMember::where('society_id', $id)
            ->where('member_id', $memberId)
            ->firstOrFail();

        $pivot->update($request->validated());

        return $this->success($pivot, 'Society member updated.');
    }

    /**
     * DELETE /api/v1/societies/{id}/members/{memberId}
     */
    public function destroy(int $id, int $memberId): JsonResponse
    {
        $pivot = SocietyMember::where('society_id', $id)
            ->where('member_id', $memberId)
            ->firstOrFail();

        $pivot->delete();

        return $this->success(null, 'Member removed from society.');
    }
}
