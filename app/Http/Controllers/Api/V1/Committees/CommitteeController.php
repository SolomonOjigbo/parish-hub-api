<?php

namespace App\Http\Controllers\Api\V1\Committees;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Committees\StoreCommitteeRequest;
use App\Http\Requests\Api\V1\Committees\UpdateCommitteeRequest;
use App\Models\Committee;
use App\Models\CommitteeActionItem;
use App\Resources\Api\V1\CommitteeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CommitteeController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:committees.view',   only: ['index', 'show']),
            new Middleware('permission:committees.create', only: ['store']),
            new Middleware('permission:committees.edit',   only: ['update']),
            new Middleware('permission:committees.delete', only: ['destroy']),
        ];
    }

    /**
     * GET /api/v1/committees
     */
    public function index(Request $request): JsonResponse
    {
        $committees = Committee::query()
            ->with(['chairMember', 'actionItems.assignedMember', 'committeeMembers.member'])
            ->withCount('committeeMembers')
            ->when($request->filled('search'), function ($q) use ($request): void {
                $q->where('name', 'like', '%' . $request->query('search') . '%');
            })
            ->orderBy('name')
            ->get();

        $nextDue = CommitteeActionItem::whereIn('committee_id', $committees->pluck('id'))
            ->where('is_completed', false)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '>=', now()->toDateString())
            ->orderBy('due_date')
            ->get()
            ->groupBy('committee_id')
            ->map(fn($g) => $g->first()->due_date?->format('Y-m-d'));

        $committees->each(function (Committee $c) use ($nextDue): void {
            $c->next_due_date = $nextDue->get($c->id);
        });

        return $this->success(
            CommitteeResource::collection($committees),
            'Committees retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/committees
     */
    public function store(StoreCommitteeRequest $request): JsonResponse
    {
        $committee = Committee::create($request->validated());
        $committee->load('chairMember');

        return $this->success(
            new CommitteeResource($committee),
            'Committee created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * GET /api/v1/committees/{id}
     */
    public function show(int $id): JsonResponse
    {
        $committee = Committee::with([
            'chairMember',
            'committeeMembers.member',
            'actionItems.assignedMember',
        ])->findOrFail($id);

        return $this->success(
            new CommitteeResource($committee),
            'Committee retrieved successfully.'
        );
    }

    /**
     * PUT /api/v1/committees/{id}
     */
    public function update(UpdateCommitteeRequest $request, int $id): JsonResponse
    {
        $committee = Committee::findOrFail($id);
        $committee->update($request->validated());
        $committee->load('chairMember');

        return $this->success(
            new CommitteeResource($committee),
            'Committee updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/committees/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $committee = Committee::findOrFail($id);
        $committee->delete();

        return $this->success(null, 'Committee deleted', 200);
    }
}
