<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\EventAttendance;
use App\Resources\Api\V1\EventAttendanceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class AttendanceController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:events.view'),
        ];
    }

    /**
     * GET /api/v1/attendance
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 25);

        $paginator = EventAttendance::with(['member.contactDetail', 'event'])
            ->when($request->filled('event_id'),  fn($q) => $q->where('event_id', $request->query('event_id')))
            ->when($request->filled('member_id'), fn($q) => $q->where('member_id', $request->query('member_id')))
            ->when($request->filled('from'),      fn($q) => $q->whereDate('checked_in_at', '>=', $request->query('from')))
            ->when($request->filled('to'),        fn($q) => $q->whereDate('checked_in_at', '<=', $request->query('to')))
            ->orderByDesc('checked_in_at')
            ->paginate($perPage);

        return $this->paginated(
            EventAttendanceResource::collection($paginator),
            [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'Attendance log retrieved successfully.'
        );
    }
}
