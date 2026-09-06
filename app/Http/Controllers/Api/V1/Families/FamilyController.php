<?php

namespace App\Http\Controllers\Api\V1\Families;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Families\AssignMemberRequest;
use App\Http\Requests\Api\V1\Families\StoreFamilyRequest;
use App\Http\Requests\Api\V1\Families\UpdateFamilyRequest;
use App\Models\Donation;
use App\Models\Family;
use App\Models\Member;
use App\Models\Offering;
use App\Models\Pledge;
use App\Models\Tithe;
use App\Resources\Api\V1\FamilyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class FamilyController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:families.view',   only: ['index', 'show']),
            new Middleware('permission:finance.view',    only: ['giving']),
            new Middleware('permission:families.create', only: ['store']),
            new Middleware('permission:families.edit',   only: ['update', 'assignMember', 'removeMember']),
            new Middleware('permission:families.delete', only: ['destroy']),
        ];
    }

    /**
     * GET /api/v1/families
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 25);

        $query = Family::query()
            ->with(['zone', 'members.contactDetail'])
            ->withCount('members')
            ->when($request->filled('search'), function ($q) use ($request): void {
                $q->where('name', 'like', '%' . $request->query('search') . '%');
            })
            ->when($request->filled('zone_id'), fn($q) => $q->where('zone_id', $request->query('zone_id')))
            ->orderBy('name');

        $paginator = $query->paginate($perPage);

        return $this->paginated(
            FamilyResource::collection($paginator),
            [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'Families retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/families
     */
    public function store(StoreFamilyRequest $request): JsonResponse
    {
        $family = Family::create($request->validated());
        $family->load(['zone', 'members.contactDetail', 'headMember']);

        return $this->success(
            new FamilyResource($family),
            'Family created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * GET /api/v1/families/{id}
     */
    public function show(int $id): JsonResponse
    {
        $family = Family::with([
            'zone',
            'members.contactDetail',
            'members.zone',
            'members.societies',
            'headMember',
        ])->findOrFail($id);

        $memberIds = $family->members->pluck('id');
        $year = now()->year;

        $totalThisYear =
            (float) Offering::whereIn('member_id', $memberIds)
                ->whereYear('collection_date', $year)->sum('amount')
            + (float) Tithe::whereIn('member_id', $memberIds)
                ->where('period_year', $year)->sum('amount')
            + (float) Donation::whereIn('member_id', $memberIds)
                ->whereYear('donation_date', $year)->sum('amount')
            + (float) Pledge::whereIn('member_id', $memberIds)
                ->sum('amount_paid');

        $payload = (new FamilyResource($family))->toArray($family->id ? request() : request());

        $payload['giving_summary'] = [
            'year'            => $year,
            'total_this_year' => round($totalThisYear, 2),
        ];

        return $this->success($payload, 'Family retrieved successfully.');
    }

    /**
     * PUT /api/v1/families/{id}
     */
    public function update(UpdateFamilyRequest $request, int $id): JsonResponse
    {
        $family = Family::findOrFail($id);
        $family->update($request->validated());
        $family->load(['zone', 'members.contactDetail', 'headMember']);

        return $this->success(
            new FamilyResource($family),
            'Family updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/families/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $family = Family::findOrFail($id);
        $family->delete();

        return $this->success(null, 'Family deleted', 200);
    }

    /**
     * POST /api/v1/families/{id}/members
     */
    public function assignMember(AssignMemberRequest $request, int $id): JsonResponse
    {
        $family = Family::findOrFail($id);
        $data   = $request->validated();

        DB::transaction(function () use ($family, $data): void {
            $member = Member::findOrFail($data['member_id']);
            $member->family_id = $family->id;

            if (!empty($data['is_family_head'])) {
                Member::where('family_id', $family->id)
                    ->where('id', '!=', $member->id)
                    ->update(['is_family_head' => false]);

                $member->is_family_head = true;
                $family->head_member_id = $member->id;
                $family->save();
            }

            $member->save();
        });

        $family->load(['zone', 'members.contactDetail', 'headMember']);

        return $this->success(
            new FamilyResource($family),
            'Member assigned to family successfully.'
        );
    }

    /**
     * DELETE /api/v1/families/{id}/members/{memberId}
     */
    public function removeMember(int $id, int $memberId): JsonResponse
    {
        $family = Family::findOrFail($id);

        $member = Member::where('id', $memberId)
            ->where('family_id', $family->id)
            ->firstOrFail();

        DB::transaction(function () use ($family, $member): void {
            $member->family_id      = null;
            $member->is_family_head = false;
            $member->save();

            if ($family->head_member_id === $member->id) {
                $family->head_member_id = null;
                $family->save();
            }
        });

        return $this->success(null, 'Member removed from family.');
    }

    /**
     * GET /api/v1/families/{id}/giving
     */
    public function giving(int $id): JsonResponse
    {
        $family = Family::with(['members'])->findOrFail($id);
        $year   = now()->year;

        $byMember = [];
        $totals = [
            'offerings' => 0.0,
            'tithes'    => 0.0,
            'donations' => 0.0,
            'pledges'   => 0.0,
        ];

        foreach ($family->members as $member) {
            $offerings = (float) Offering::where('member_id', $member->id)
                ->whereYear('collection_date', $year)->sum('amount');
            $tithes    = (float) Tithe::where('member_id', $member->id)
                ->where('period_year', $year)->sum('amount');
            $donations = (float) Donation::where('member_id', $member->id)
                ->whereYear('donation_date', $year)->sum('amount');
            $pledges   = (float) Pledge::where('member_id', $member->id)->sum('amount_paid');

            $totals['offerings'] += $offerings;
            $totals['tithes']    += $tithes;
            $totals['donations'] += $donations;
            $totals['pledges']   += $pledges;

            $byMember[] = [
                'member_id' => $member->id,
                'full_name' => $member->full_name,
                'offerings' => round($offerings, 2),
                'tithes'    => round($tithes, 2),
                'donations' => round($donations, 2),
                'pledges'   => round($pledges, 2),
                'total'     => round($offerings + $tithes + $donations + $pledges, 2),
            ];
        }

        $totals = array_map(fn($v) => round($v, 2), $totals);
        $totals['grand_total'] = round(array_sum($totals), 2);

        return $this->success(
            [
                'family_id' => $family->id,
                'year'      => $year,
                'totals'    => $totals,
                'members'   => $byMember,
            ],
            'Family giving retrieved successfully.'
        );
    }
}
