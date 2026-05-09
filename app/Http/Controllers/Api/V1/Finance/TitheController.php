<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Finance\StoreTitheRequest;
use App\Http\Requests\Api\V1\Finance\UpdateTitheRequest;
use App\Models\Tithe;
use App\Resources\Api\V1\TitheResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TitheController extends BaseApiController
{
    /**
     * Display a listing of tithes.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tithe::with(['member', 'recorder']);

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->has('period_month')) {
            $query->where('period_month', $request->period_month);
        }

        if ($request->has('period_year')) {
            $query->where('period_year', $request->period_year);
        }

        $tithes = $query->orderBy('payment_date', 'desc')->paginate($request->input('per_page', 15));

        return $this->paginated(
            TitheResource::collection($tithes),
            paginator_meta($tithes),
        );
    }

    /**
     * Store a newly created tithe.
     */
    public function store(StoreTitheRequest $request): JsonResponse
    {
        $tithe = Tithe::create(array_merge($request->validated(), [
            'recorded_by' => $request->user()->id,
        ]));

        return $this->success(new TitheResource($tithe->load(['member', 'recorder'])), 'Tithe created successfully', 201);
    }

    /**
     * Display the specified tithe.
     */
    public function show(Tithe $tithe): JsonResponse
    {
        return $this->success(new TitheResource($tithe->load(['member', 'recorder'])));
    }

    /**
     * Update the specified tithe.
     */
    public function update(UpdateTitheRequest $request, Tithe $tithe): JsonResponse
    {
        $tithe->update($request->validated());

        return $this->success(new TitheResource($tithe->load(['member', 'recorder'])), 'Tithe updated successfully');
    }

    /**
     * Remove the specified tithe.
     */
    public function destroy(Tithe $tithe): JsonResponse
    {
        $tithe->delete();

        return $this->success(null, 'Tithe deleted successfully');
    }

    /**
     * Get tithes for a specific member.
     */
    public function member(int $memberId, Request $request): JsonResponse
    {
        $tithes = Tithe::with(['member', 'recorder'])
            ->where('member_id', $memberId)
            ->orderBy('payment_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated(
            TitheResource::collection($tithes),
            paginator_meta($tithes),
        );
    }
}
