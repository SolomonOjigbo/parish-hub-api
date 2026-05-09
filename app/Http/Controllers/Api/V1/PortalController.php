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

        $member = Member::with(['family', 'societies'])->find($user->member_id);

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

        $request->validate([
            'primary_phone' => ['sometimes', 'string', 'max:20'],
            'whatsapp_number' => ['sometimes', 'string', 'max:20'],
            'email' => ['sometimes', 'email', 'max:255'],
            'address_line1' => ['sometimes', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'string', 'max:255'],
            'lga' => ['sometimes', 'string', 'max:100'],
            'photo' => ['sometimes', 'string'],
        ]);

        $member = Member::find($user->member_id);
        $member->update($request->only([
            'primary_phone',
            'whatsapp_number',
            'email',
            'address_line1',
            'address_line2',
            'lga',
            'photo',
        ]));

        return $this->success(new MemberResource($member->load(['family', 'societies'])), 'Profile updated successfully');
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
        $url = Storage::disk('public')->url($path);

        $member = Member::find($user->member_id);
        $member->update(['photo' => $url]);

        return $this->success(['photo' => $url], 'Photo uploaded successfully');
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

        $offerings = Offering::where('member_id', $memberId)
            ->whereBetween('collection_date', [now()->startOfYear(), now()])
            ->sum('amount');

        $tithes = Tithe::where('member_id', $memberId)
            ->whereBetween('payment_date', [now()->startOfYear(), now()])
            ->sum('amount');

        $donations = Donation::where('member_id', $memberId)
            ->whereBetween('donation_date', [now()->startOfYear(), now()])
            ->sum('amount');

        return $this->success([
            'offerings_ytd' => (float) $offerings,
            'tithes_ytd' => (float) $tithes,
            'donations_ytd' => (float) $donations,
            'total_ytd' => (float) ($offerings + $tithes + $donations),
        ]);
    }

    /**
     * Get upcoming events with registration status.
     */
    public function events(Request $request): JsonResponse
    {
        $this->authorize('portal.access');

        $user = $request->user();

        $events = Event::where('start_date', '>=', now())
            ->where('requires_registration', true)
            ->orderBy('start_date')
            ->get();

        $eventsWithStatus = $events->map(function ($event) use ($user) {
            $isRegistered = EventRegistration::where('event_id', $event->id)
                ->where('member_id', $user->member_id)
                ->exists();

            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date?->toIso8601String(),
                'end_date' => $event->end_date?->toIso8601String(),
                'location' => $event->location,
                'is_registered' => $isRegistered,
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
            'registered_by' => $user->id,
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

        $family = $member->family->load('members');

        return $this->success(new FamilyResource($family));
    }
}
