<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Member;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Family;
use App\Models\Offering;
use App\Models\Tithe;
use App\Models\Donation;
use App\Resources\Api\V1\MemberResource;
use App\Resources\Api\V1\FamilyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortalController extends BaseApiController
{
    /**
     * Get authenticated user's linked member profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $this->authorize('portal.access');

        $user = $request->user();

        if (!$user->member_id) {
            return $this->error('No linked member profile found', 404);
        }

        $member = Member::with(['contactDetail', 'family', 'societies', 'sacramentalRecords'])->find($user->member_id);

        return $this->success(new MemberResource($member));
    }

    /**
     * Update member's own profile (limited fields).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $this->authorize('portal.access');

        $user = $request->user();

        if (!$user->member_id) {
            return $this->error('No linked member profile found', 404);
        }

        $contactFields = $request->validate([
            'primary_phone' => ['sometimes', 'string', 'max:20'],
            'whatsapp_number' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lga' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $member = Member::findOrFail($user->member_id);

        if (!empty($contactFields)) {
            $member->contactDetail()->updateOrCreate(
                ['member_id' => $member->id],
                $contactFields
            );
        }

        return $this->success(
            new MemberResource($member->load(['contactDetail', 'family', 'societies'])),
            'Profile updated successfully'
        );
    }

    /**
     * Upload member photo.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $this->authorize('portal.access');

        $user = $request->user();

        if (!$user->member_id) {
            return $this->error('No linked member profile found', 404);
        }

        $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $path = $request->file('photo')->store('members/photos', 'public');

        $member = Member::findOrFail($user->member_id);
        $member->update(['photo_path' => $path]);

        return $this->success([
            'photo_path' => $path,
            'photo_url' => Storage::disk('public')->url($path),
        ], 'Photo uploaded successfully');
    }

    /**
     * Get member's own giving history.
     */
    public function giving(Request $request): JsonResponse
    {
        $this->authorize('portal.access');

        $user = $request->user();

        if (!$user->member_id) {
            return $this->error('No linked member profile found', 404);
        }

        $memberId = $user->member_id;

        $offerings = Offering::where('member_id', $memberId)->orderByDesc('collection_date')->get();
        $tithes = Tithe::where('member_id', $memberId)->orderByDesc('payment_date')->get();
        $donations = Donation::where('member_id', $memberId)->orderByDesc('donation_date')->get();
        $pledges = \App\Models\Pledge::with('payments')->where('member_id', $memberId)->orderByDesc('start_date')->get();

        $yearStart = now()->startOfYear();
        $offeringsYtd = (float) $offerings->where('collection_date', '>=', $yearStart)->sum('amount');
        $tithesYtd = (float) $tithes->where('payment_date', '>=', $yearStart)->sum('amount');
        $donationsYtd = (float) $donations->where('donation_date', '>=', $yearStart)->sum('amount');

        return $this->success([
            'offerings' => \App\Resources\Api\V1\OfferingResource::collection($offerings),
            'tithes' => \App\Resources\Api\V1\TitheResource::collection($tithes),
            'donations' => \App\Resources\Api\V1\DonationResource::collection($donations),
            'pledges' => \App\Resources\Api\V1\PledgeResource::collection($pledges),
            'offerings_ytd' => $offeringsYtd,
            'tithes_ytd' => $tithesYtd,
            'donations_ytd' => $donationsYtd,
            'total_ytd' => $offeringsYtd + $tithesYtd + $donationsYtd,
        ]);
    }

    /**
     * GET /api/v1/portal/giving/statement?year=YYYY — own annual statement PDF.
     */
    public function givingStatement(Request $request): \Illuminate\Http\Response|JsonResponse
    {
        $this->authorize('portal.access');

        $user = $request->user();

        if (!$user->member_id) {
            return $this->error('No linked member profile found', 404);
        }

        $member = Member::with('contactDetail')->findOrFail($user->member_id);
        $year = (int) $request->query('year', now()->year);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.giving-statement', array_merge(
            ['member' => $member],
            \App\Http\Controllers\Api\V1\Members\MemberController::buildGivingStatementData($member, $year)
        ));

        return $pdf->download("giving-statement-{$year}.pdf");
    }

    /**
     * Get upcoming events with registration status.
     */
    public function events(Request $request): JsonResponse
    {
        $this->authorize('portal.access');

        $user = $request->user();

        $events = Event::where('start_datetime', '>=', now())
            ->orderBy('start_datetime')
            ->get();

        $registeredIds = EventRegistration::whereIn('event_id', $events->pluck('id'))
            ->where('member_id', $user->member_id)
            ->pluck('event_id')
            ->all();

        $eventsWithStatus = $events->map(function ($event) use ($registeredIds) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'type' => $event->type,
                'description' => $event->description,
                'start_datetime' => $event->start_datetime?->toIso8601String(),
                'end_datetime' => $event->end_datetime?->toIso8601String(),
                'location' => $event->location,
                'requires_registration' => (bool) $event->requires_registration,
                'is_registered' => in_array($event->id, $registeredIds, true),
            ];
        });

        return $this->success($eventsWithStatus);
    }

    /**
     * Self-register for an event.
     */
    public function registerEvent(Request $request, int $eventId): JsonResponse
    {
        $this->authorize('portal.access');

        $user = $request->user();

        if (!$user->member_id) {
            return $this->error('No linked member profile found', 404);
        }

        $event = Event::findOrFail($eventId);

        if (!$event->requires_registration) {
            return $this->error('This event does not require registration', 400);
        }

        $existing = EventRegistration::where('event_id', $eventId)
            ->where('member_id', $user->member_id)
            ->first();

        if ($existing) {
            return $this->error('Already registered for this event', 400);
        }

        EventRegistration::create([
            'event_id' => $eventId,
            'member_id' => $user->member_id,
            'registered_at' => now(),
            'payment_status' => $event->is_retreat ? 'pending' : 'na',
        ]);

        return $this->success(null, 'Event registration successful', 201);
    }

    /**
     * Get member's family.
     */
    public function family(Request $request): JsonResponse
    {
        $this->authorize('portal.access');

        $user = $request->user();

        if (!$user->member_id) {
            return $this->error('No linked member profile found', 404);
        }

        $member = Member::with('family')->find($user->member_id);

        if (!$member->family) {
            return $this->error('No family linked to this member', 404);
        }

        $family = $member->family->load('members.contactDetail');

        return $this->success(new FamilyResource($family));
    }
}
