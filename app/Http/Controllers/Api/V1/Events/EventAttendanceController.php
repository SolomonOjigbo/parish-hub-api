<?php

namespace App\Http\Controllers\Api\V1\Events;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Events\MarkAttendanceRequest;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventRegistration;
use App\Resources\Api\V1\EventAttendanceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class EventAttendanceController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:events.view', only: ['index']),
            new Middleware('permission:events.edit', only: ['mark']),
        ];
    }

    /**
     * POST /api/v1/events/{id}/attendance
     */
    public function mark(MarkAttendanceRequest $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $data  = $request->validated();
        $userId = $request->user()->id;

        DB::transaction(function () use ($event, $data, $userId): void {
            if (!empty($data['member_ids'])) {
                foreach ($data['member_ids'] as $memberId) {
                    EventAttendance::firstOrCreate(
                        ['event_id' => $event->id, 'member_id' => $memberId],
                        ['checked_in_at' => now(), 'recorded_by' => $userId]
                    );
                }
            } elseif (!empty($data['member_id'])) {
                $action = $data['action'] ?? 'check_in';

                if ($action === 'remove') {
                    EventAttendance::where('event_id', $event->id)
                        ->where('member_id', $data['member_id'])
                        ->delete();
                } else {
                    EventAttendance::firstOrCreate(
                        ['event_id' => $event->id, 'member_id' => $data['member_id']],
                        ['checked_in_at' => now(), 'recorded_by' => $userId]
                    );
                }
            }
        });

        $checkedIn = EventAttendance::where('event_id', $event->id)->count();

        return $this->success(
            ['attendance_count' => $checkedIn],
            'Attendance recorded successfully.'
        );
    }

    /**
     * GET /api/v1/events/{id}/attendance
     */
    public function index(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $attendances = EventAttendance::with('member.contactDetail')
            ->where('event_id', $event->id)
            ->orderBy('checked_in_at')
            ->get();

        $checkedIn   = $attendances->count();
        $registered  = EventRegistration::where('event_id', $event->id)->count();
        $percentage  = $registered > 0
            ? round(($checkedIn / $registered) * 100, 1)
            : 0.0;

        return $this->success(
            [
                'data'    => EventAttendanceResource::collection($attendances),
                'summary' => [
                    'checked_in' => $checkedIn,
                    'registered' => $registered,
                    'percentage' => $percentage,
                ],
            ],
            'Attendance retrieved successfully.'
        );
    }
}
