<?php

namespace App\Http\Controllers\Api\V1\Committees;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Committees\StoreActionItemRequest;
use App\Http\Requests\Api\V1\Committees\UpdateActionItemRequest;
use App\Models\Committee;
use App\Models\CommitteeActionItem;
use App\Resources\Api\V1\CommitteeActionItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CommitteeActionItemController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:committees.view',   only: ['index']),
            new Middleware('permission:committees.edit',   only: ['store', 'update']),
            new Middleware('permission:committees.delete', only: ['destroy']),
        ];
    }

    /**
     * GET /api/v1/committees/{id}/action-items
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $committee = Committee::findOrFail($id);

        $items = CommitteeActionItem::with('assignedMember')
            ->where('committee_id', $committee->id)
            ->when($request->filled('is_completed'), function ($q) use ($request): void {
                $q->where('is_completed', filter_var($request->query('is_completed'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->get();

        return $this->success(
            CommitteeActionItemResource::collection($items),
            'Action items retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/committees/{id}/action-items
     */
    public function store(StoreActionItemRequest $request, int $id): JsonResponse
    {
        $committee = Committee::findOrFail($id);
        $data      = $request->validated();

        if (!empty($data['is_completed'])) {
            $data['completed_at'] = now();
        }

        $item = CommitteeActionItem::create(array_merge(
            $data,
            ['committee_id' => $committee->id]
        ));
        $item->load('assignedMember');

        return $this->success(
            new CommitteeActionItemResource($item),
            'Action item created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * PUT /api/v1/committees/{id}/action-items/{itemId}
     */
    public function update(UpdateActionItemRequest $request, int $id, int $itemId): JsonResponse
    {
        $item = CommitteeActionItem::where('committee_id', $id)
            ->where('id', $itemId)
            ->firstOrFail();

        $data = $request->validated();

        if (array_key_exists('is_completed', $data)) {
            if ($data['is_completed'] && !$item->is_completed) {
                $data['completed_at'] = now();
            } elseif (!$data['is_completed']) {
                $data['completed_at'] = null;
            }
        }

        $item->update($data);
        $item->load('assignedMember');

        return $this->success(
            new CommitteeActionItemResource($item),
            'Action item updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/committees/{id}/action-items/{itemId}
     */
    public function destroy(int $id, int $itemId): JsonResponse
    {
        $item = CommitteeActionItem::where('committee_id', $id)
            ->where('id', $itemId)
            ->firstOrFail();

        $item->delete();

        return $this->success(null, 'Action item deleted.');
    }
}
