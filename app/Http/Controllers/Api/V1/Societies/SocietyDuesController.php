<?php

namespace App\Http\Controllers\Api\V1\Societies;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Societies\StoreSocietyDuesRequest;
use App\Models\Society;
use App\Models\SocietyDue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SocietyDuesController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:societies.view', only: ['index', 'matrix']),
            new Middleware('permission:societies.edit', only: ['store']),
        ];
    }

    /**
     * GET /api/v1/societies/{id}/dues
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $society = Society::findOrFail($id);
        $year    = (int) $request->query('year', now()->year);

        $dues = SocietyDue::with('member:id,first_name,last_name,other_name,membership_number')
            ->where('society_id', $society->id)
            ->where('period_year', $year)
            ->orderBy('period_month')
            ->get();

        return $this->success($dues, 'Dues retrieved successfully.');
    }

    /**
     * POST /api/v1/societies/{id}/dues
     */
    public function store(StoreSocietyDuesRequest $request, int $id): JsonResponse
    {
        $society = Society::findOrFail($id);
        $data    = $request->validated();

        $due = SocietyDue::create([
            'society_id'   => $society->id,
            'member_id'    => $data['member_id'],
            'period_month' => $data['period_month'],
            'period_year'  => $data['period_year'],
            'amount'       => $data['amount'],
            'paid_at'      => $data['paid_at'] ?? now(),
            'recorded_by'  => $request->user()?->id,
        ]);

        return $this->success($due, 'Dues recorded successfully.', Response::HTTP_CREATED);
    }

    /**
     * GET /api/v1/societies/{id}/dues/matrix
     */
    public function matrix(Request $request, int $id): JsonResponse
    {
        $society = Society::with('members:id,first_name,last_name,other_name')->findOrFail($id);
        $year    = (int) $request->query('year', now()->year);

        $paidByMember = SocietyDue::where('society_id', $society->id)
            ->where('period_year', $year)
            ->get(['member_id', 'period_month'])
            ->groupBy('member_id')
            ->map(fn($rows) => $rows->pluck('period_month')->unique()->all());

        $rows = $society->members->map(function ($member) use ($paidByMember): array {
            $months = $paidByMember->get($member->id, []);
            $dues   = [];
            for ($m = 1; $m <= 12; $m++) {
                $dues[(string) $m] = in_array($m, $months, true);
            }

            return [
                'id'   => $member->id,
                'name' => $member->full_name,
                'dues' => $dues,
            ];
        });

        return $this->success(
            [
                'year'    => $year,
                'members' => $rows,
            ],
            'Dues matrix retrieved successfully.'
        );
    }
}
