<?php

namespace App\Http\Controllers\Api\V1\Events;

use App\Exports\EventRegistrationsExport;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Mail\EventReminderMail;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Resources\Api\V1\EventRegistrationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EventRegistrationController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:events.view', only: ['index']),
            new Middleware('permission:events.edit', only: ['register', 'cancel']),
        ];
    }

    /**
     * POST /api/v1/events/{id}/register
     */
    public function register(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'member_id' => ['nullable', 'exists:members,id'],
            'notes'     => ['nullable', 'string'],
        ]);

        $event     = Event::findOrFail($id);
        $memberId  = $request->input('member_id') ?? $request->user()?->member_id;

        if (!$memberId) {
            return $this->error('No member context available for registration.', 422);
        }

        if ($event->start_datetime->isPast()) {
            return $this->error('Cannot register for a past event.', 422);
        }

        if ($event->max_capacity) {
            $count = EventRegistration::where('event_id', $event->id)->count();
            if ($count >= $event->max_capacity) {
                return $this->error('Event is at full capacity.', 422);
            }
        }

        $exists = EventRegistration::where('event_id', $event->id)
            ->where('member_id', $memberId)
            ->exists();

        if ($exists) {
            return $this->error('Member already registered for this event.', 422);
        }

        $registration = EventRegistration::create([
            'event_id'       => $event->id,
            'member_id'      => $memberId,
            'registered_at'  => now(),
            'payment_status' => $event->is_retreat ? 'pending' : 'na',
            'notes'          => $request->input('notes'),
        ]);

        $registration->load('member.contactDetail');

        $email = $registration->member?->contactDetail?->email;
        if ($email) {
            Mail::to($email)->queue(new EventReminderMail($event, $registration->member));
        }

        return $this->success(
            new EventRegistrationResource($registration),
            'Registration successful.',
            Response::HTTP_CREATED
        );
    }

    /**
     * DELETE /api/v1/events/{id}/register
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $memberId = $request->input('member_id') ?? $request->user()?->member_id;

        if (!$memberId) {
            return $this->error('No member context available.', 422);
        }

        $registration = EventRegistration::where('event_id', $id)
            ->where('member_id', $memberId)
            ->firstOrFail();

        $registration->delete();

        return $this->success(null, 'Registration cancelled.');
    }

    /**
     * GET /api/v1/events/{id}/registrations
     */
    public function index(Request $request, int $id): JsonResponse|BinaryFileResponse
    {
        $event = Event::findOrFail($id);

        $query = EventRegistration::with('member.contactDetail')
            ->where('event_id', $event->id)
            ->orderBy('registered_at');

        if (strtolower((string) $request->query('format')) === 'excel') {
            return Excel::download(
                new EventRegistrationsExport($query->get()),
                "event-{$event->id}-registrations.xlsx"
            );
        }

        $perPage   = (int) $request->query('per_page', 25);
        $paginator = $query->paginate($perPage);

        return $this->paginated(
            EventRegistrationResource::collection($paginator),
            [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'Registrations retrieved successfully.'
        );
    }
}
