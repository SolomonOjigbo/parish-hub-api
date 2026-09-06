<?php

namespace App\Http\Controllers\Api\V1\Societies;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Societies\StoreSocietyRequest;
use App\Http\Requests\Api\V1\Societies\UpdateSocietyRequest;
use App\Models\Society;
use App\Models\SocietyMeeting;
use App\Resources\Api\V1\SocietyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SocietyController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:societies.view',   only: ['index', 'show']),
            new Middleware('permission:societies.create', only: ['store']),
            new Middleware('permission:societies.edit',   only: ['update']),
            new Middleware('permission:societies.delete', only: ['destroy']),
        ];
    }

    /**
     * GET /api/v1/societies
     */
    public function index(Request $request): JsonResponse
    {
        $societies = Society::query()
            ->withCount('members')
            ->with('members:id,first_name,last_name,other_name')
            ->when($request->filled('search'), function ($q) use ($request): void {
                $q->where('name', 'like', '%' . $request->query('search') . '%');
            })
            ->orderBy('name')
            ->get();

        $nextMeetings = SocietyMeeting::whereIn('society_id', $societies->pluck('id'))
            ->whereDate('meeting_date', '>=', now()->toDateString())
            ->orderBy('meeting_date')
            ->get()
            ->groupBy('society_id')
            ->map(fn($group) => $group->first()->meeting_date?->format('Y-m-d'));

        $societies->each(function (Society $s) use ($nextMeetings): void {
            $s->next_meeting_date = $nextMeetings->get($s->id);
        });

        return $this->success(
            SocietyResource::collection($societies),
            'Societies retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/societies
     */
    public function store(StoreSocietyRequest $request): JsonResponse
    {
        $society = Society::create($request->validated());

        return $this->success(
            new SocietyResource($society),
            'Society created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * GET /api/v1/societies/{id}
     */
    public function show(int $id): JsonResponse
    {
        $society = Society::with(['members.contactDetail'])
            ->withCount('members')
            ->findOrFail($id);

        $next = SocietyMeeting::where('society_id', $society->id)
            ->whereDate('meeting_date', '>=', now()->toDateString())
            ->orderBy('meeting_date')
            ->first();
        $society->next_meeting_date = $next?->meeting_date?->format('Y-m-d');

        $year = now()->year;
        $duesPaid = \App\Models\SocietyDue::where('society_id', $society->id)
            ->where('period_year', $year)
            ->select('member_id')
            ->groupBy('member_id')
            ->havingRaw('COUNT(*) >= 12')
            ->pluck('member_id')
            ->all();

        $society->members->each(function ($member) use ($duesPaid): void {
            $member->pivot->dues_status = in_array($member->id, $duesPaid, true) ? 'paid' : 'pending';
        });

        return $this->success(
            new SocietyResource($society),
            'Society retrieved successfully.'
        );
    }

    /**
     * PUT /api/v1/societies/{id}
     */
    public function update(UpdateSocietyRequest $request, int $id): JsonResponse
    {
        $society = Society::findOrFail($id);
        $society->update($request->validated());

        return $this->success(
            new SocietyResource($society),
            'Society updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/societies/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $society = Society::findOrFail($id);
        $society->delete();

        return $this->success(null, 'Society deleted', 200);
    }
}
