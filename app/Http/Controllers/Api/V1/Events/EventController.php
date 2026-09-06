<?php

namespace App\Http\Controllers\Api\V1\Events;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Events\StoreEventRequest;
use App\Http\Requests\Api\V1\Events\UpdateEventRequest;
use App\Jobs\EventReminderJob;
use App\Models\Event;
use App\Resources\Api\V1\EventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class EventController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:events.view',   only: ['index', 'show']),
            new Middleware('permission:events.create', only: ['store']),
            new Middleware('permission:events.edit',   only: ['update', 'sendReminders']),
            new Middleware('permission:events.delete', only: ['destroy']),
        ];
    }

    /**
     * GET /api/v1/events
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 25);

        $query = Event::query()
            ->withCount(['registrations', 'attendances'])
            ->with(['registrations:id,event_id,member_id', 'attendances:id,event_id,member_id'])
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->query('type')))
            ->when($request->filled('from'), fn($q) => $q->where('start_datetime', '>=', $request->query('from')))
            ->when($request->filled('to'),   fn($q) => $q->where('start_datetime', '<=', $request->query('to')))
            ->when(filter_var($request->query('upcoming'), FILTER_VALIDATE_BOOLEAN), function ($q): void {
                $q->where('start_datetime', '>=', now());
            })
            ->when($request->filled('include_in_bulletin'), function ($q) use ($request): void {
                $q->where('include_in_bulletin', filter_var($request->query('include_in_bulletin'), FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('start_datetime', 'desc');

        $paginator = $query->paginate($perPage);

        return $this->paginated(
            EventResource::collection($paginator),
            [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'Events retrieved successfully.'
        );
    }

    /**
     * POST /api/v1/events
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = Event::create(array_merge(
            $request->validated(),
            ['created_by' => $request->user()->id]
        ));

        if ($event->requires_registration && $event->start_datetime->diffInHours(now()) > 24) {
            $reminderAt = $event->start_datetime->copy()->subDay();
            EventReminderJob::dispatch($event->id)->delay($reminderAt);
        }

        $event->loadCount(['registrations', 'attendances']);

        return $this->success(
            new EventResource($event),
            'Event created successfully.',
            Response::HTTP_CREATED
        );
    }

    /**
     * GET /api/v1/events/{id}
     */
    public function show(int $id): JsonResponse
    {
        $event = Event::withCount(['registrations', 'attendances'])
            ->with(['creator', 'registrations:id,event_id,member_id', 'attendances:id,event_id,member_id'])
            ->findOrFail($id);

        return $this->success(
            new EventResource($event),
            'Event retrieved successfully.'
        );
    }

    /**
     * PUT /api/v1/events/{id}
     */
    public function update(UpdateEventRequest $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $event->update($request->validated());
        $event->loadCount(['registrations', 'attendances']);

        return $this->success(
            new EventResource($event),
            'Event updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/events/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $event->delete();

        return $this->success(null, 'Event deleted', 200);
    }

    /**
     * POST /api/v1/events/{id}/reminders/send
     */
    public function sendReminders(int $id): JsonResponse
    {
        $event = Event::withCount('registrations')->findOrFail($id);

        if ($event->start_datetime->isPast()) {
            return $this->error('Cannot send reminders for a past event.', 422);
        }

        if ($event->registrations_count === 0) {
            return $this->error('This event has no registrations to remind.', 422);
        }

        EventReminderJob::dispatch($event->id);

        return $this->success(
            ['recipient_count' => $event->registrations_count],
            'Event reminders queued successfully.'
        );
    }
}
