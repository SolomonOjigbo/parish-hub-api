<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Member;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PublicController extends BaseApiController
{
    /**
     * Public member registration.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date'],
            'gender' => ['required', 'in:male,female'],
            'primary_phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'lga' => ['nullable', 'string', 'max:100'],
            'zone_id' => ['nullable', 'exists:zones,id'],
        ]);

        $member = Member::create(array_merge($request->validated(), [
            'is_active' => true,
            'status' => 'active',
        ]));

        // Send welcome email if email provided
        if ($request->has('email')) {
            try {
                Mail::raw("Welcome to St. Ferdinand Catholic Church!\n\nYour registration has been received. We will contact you shortly with more information about our parish community.\n\nGod bless you.", function ($message) use ($request) {
                    $message->to($request->email)
                        ->subject('Welcome to St. Ferdinand Catholic Church');
                });
            } catch (\Exception $e) {
                // Continue even if email fails
            }
        }

        return $this->success(['member_id' => $member->id], 'Registration successful', 201);
    }

    /**
     * Visitor card submission.
     */
    public function visitor(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'heard_from' => ['nullable', 'string', 'max:255'],
        ]);

        $member = Member::create([
            'first_name' => $request->name,
            'last_name' => '',
            'primary_phone' => $request->phone,
            'email' => $request->email,
            'is_active' => true,
            'status' => 'active',
            'notes' => 'Visitor card submission. Heard from: ' . ($request->heard_from ?? 'N/A'),
        ]);

        // Send thank-you email if email provided
        if ($request->has('email')) {
            try {
                Mail::raw("Thank you for visiting St. Ferdinand Catholic Church!\n\nWe were blessed to have you worship with us. We hope to see you again soon.\n\nIf you have any questions about our parish or would like to learn more about our community, please don't hesitate to contact us.\n\nGod bless you.", function ($message) use ($request) {
                    $message->to($request->email)
                        ->subject('Thank You for Visiting St. Ferdinand Catholic Church');
                });
            } catch (\Exception $e) {
                // Continue even if email fails
            }
        }

        return $this->success(['member_id' => $member->id], 'Visitor card submitted successfully', 201);
    }

    /**
     * Get upcoming public events.
     */
    public function events(Request $request): JsonResponse
    {
        $events = Event::where('start_date', '>=', now())
            ->where('start_date', '<=', now()->addDays(30))
            ->orderBy('start_date')
            ->get();

        $publicEvents = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date?->toIso8601String(),
                'end_date' => $event->end_date?->toIso8601String(),
                'location' => $event->location,
                'requires_registration' => $event->requires_registration,
            ];
        });

        return $this->success($publicEvents);
    }
}
