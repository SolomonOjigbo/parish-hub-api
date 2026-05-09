<?php

namespace App\Http\Controllers\Api\V1\Societies;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Societies\StoreSocietyMeetingRequest;
use App\Http\Requests\Api\V1\Societies\UpdateSocietyMeetingRequest;
use App\Models\Society;
use App\Models\SocietyMeeting;
use App\Resources\Api\V1\SocietyMeetingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class SocietyMeetingController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:societies.view', only: ['index']),
            new Middleware('permission:societies.edit', only: ['store', 'update', 'uploadMinutes']),
        ];
    }

    /**
     * GET /api/v1/societies/{id}/meetings
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $society = Society::findOrFail($id);
        $perPage = (int) $request->query('per_page', 25);

        $paginator = SocietyMeeting::where('society_id', $society->id)
            ->orderByDesc('meeting_date')
            ->paginate($perPage);

        return $this->paginated(
            SocietyMeetingResource::collection($paginator),
            [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'Meetings retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/societies/{id}/meetings
     */
    public function store(StoreSocietyMeetingRequest $request, int $id): JsonResponse
    {
        $society = Society::findOrFail($id);

        $meeting = SocietyMeeting::create(array_merge(
            $request->validated(),
            ['society_id' => $society->id]
        ));

        return $this->success(
            new SocietyMeetingResource($meeting),
            'Meeting created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * PUT /api/v1/societies/{id}/meetings/{meetingId}
     */
    public function update(UpdateSocietyMeetingRequest $request, int $id, int $meetingId): JsonResponse
    {
        $meeting = SocietyMeeting::where('society_id', $id)
            ->where('id', $meetingId)
            ->firstOrFail();

        $meeting->update($request->validated());

        return $this->success(
            new SocietyMeetingResource($meeting),
            'Meeting updated successfully.'
        );
    }

    /**
     * POST /api/v1/societies/{id}/meetings/{meetingId}/minutes
     */
    public function uploadMinutes(Request $request, int $id, int $meetingId): JsonResponse
    {
        $request->validate([
            'minutes' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $meeting = SocietyMeeting::where('society_id', $id)
            ->where('id', $meetingId)
            ->firstOrFail();

        $file     = $request->file('minutes');
        $filename = sprintf('meeting-%d-%s.pdf', $meeting->id, now()->format('YmdHis'));
        $path     = $file->storeAs(
            "documents/minutes/{$id}",
            $filename,
            'public'
        );

        $meeting->update([
            'minutes_path'   => $path,
            'minutes_status' => 'filed',
        ]);

        return $this->success(
            [
                'minutes_path' => $path,
                'minutes_url'  => Storage::disk('public')->url($path),
            ],
            'Minutes uploaded successfully.'
        );
    }
}
