<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreZoneRequest;
use App\Http\Requests\Api\V1\UpdateZoneRequest;
use App\Models\Zone;
use App\Resources\Api\V1\ZoneResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ZoneController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:members.view', only: ['index', 'show']),
            new Middleware('role:super_admin',        only: ['store', 'update', 'destroy']),
        ];
    }

    /**
     * GET /api/v1/zones
     */
    public function index(): JsonResponse
    {
        $zones = Zone::withCount(['members', 'families'])
            ->orderBy('name')
            ->get();

        return $this->success(
            ZoneResource::collection($zones),
            'Zones retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/zones
     */
    public function store(StoreZoneRequest $request): JsonResponse
    {
        $zone = Zone::create($request->validated());

        return $this->success(
            new ZoneResource($zone),
            'Zone created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * GET /api/v1/zones/{id}
     */
    public function show(int $id): JsonResponse
    {
        $zone = Zone::withCount(['members', 'families'])->findOrFail($id);

        return $this->success(
            new ZoneResource($zone),
            'Zone retrieved successfully.'
        );
    }

    /**
     * PUT /api/v1/zones/{id}
     */
    public function update(UpdateZoneRequest $request, int $id): JsonResponse
    {
        $zone = Zone::findOrFail($id);
        $zone->update($request->validated());

        return $this->success(
            new ZoneResource($zone),
            'Zone updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/zones/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $zone = Zone::findOrFail($id);
        $zone->delete();

        return $this->success(null, 'Zone deleted', 200);
    }
}
