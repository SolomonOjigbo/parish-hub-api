<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Event;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PublicController extends BaseApiController
{
    /**
     * Public member registration.
     *
     * Self-registrations are created as `inactive` so the parish office
     * reviews and activates them before they appear in the roster.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'other_name' => ['nullable', 'string', 'max:255'],
            'baptismal_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['required', 'in:male,female'],
            'occupation' => ['nullable', 'string', 'max:150'],
            'primary_phone' => ['required', 'string', 'max:20'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'lga' => ['nullable', 'string', 'max:100'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'marital_status' => ['nullable', 'in:single,married,widowed,divorced,religious'],
            'baptism_date' => ['nullable', 'date'],
            'baptism_church' => ['nullable', 'string', 'max:255'],
        ]);

        $member = DB::transaction(function () use ($data): Member {
            $member = Member::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'other_name' => $data['other_name'] ?? null,
                'baptismal_name' => $data['baptismal_name'] ?? null,
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'gender' => $data['gender'],
                'occupation' => $data['occupation'] ?? null,
                'zone_id' => $data['zone_id'] ?? null,
                'marital_status' => $data['marital_status'] ?? 'single',
                'status' => 'inactive',
                'date_joined' => now()->toDateString(),
                'notes' => 'Self-registered via public form — pending parish office review.',
            ]);

            $member->contactDetail()->create([
                'primary_phone' => $data['primary_phone'],
                'whatsapp_number' => $data['whatsapp_number'] ?? null,
                'email' => $data['email'] ?? null,
                'address_line1' => $data['address_line1'],
                'lga' => $data['lga'] ?? null,
            ]);

            if (!empty($data['baptism_date']) || !empty($data['baptism_church'])) {
                $member->sacramentalRecords()->create([
                    'type' => 'baptism',
                    'date' => $data['baptism_date'] ?? null,
                    'church' => $data['baptism_church'] ?? null,
                ]);
            }

            return $member;
        });

        if (!empty($data['email'])) {
            $parishName = Setting::get('parish_name', 'St. Ferdinand Catholic Church');

            try {
                Mail::raw("Welcome to {$parishName}!\n\nYour registration has been received. We will contact you shortly with more information about our parish community.\n\nGod bless you.", function ($message) use ($data, $parishName) {
                    $message->to($data['email'])
                        ->subject("Welcome to {$parishName}");
                });
            } catch (\Exception $e) {
                // Continue even if email fails
            }
        }

        return $this->success([
            'member_id' => $member->id,
            'membership_number' => $member->membership_number,
        ], 'Registration successful', 201);
    }

    /**
     * Visitor card submission.
     */
    public function visitor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'heard_from' => ['nullable', 'string', 'max:255'],
            'prayer_request' => ['nullable', 'string', 'max:2000'],
        ]);

        $visitor = Visitor::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'heard_from' => $data['heard_from'] ?? null,
            'notes' => !empty($data['prayer_request']) ? 'Prayer request: ' . $data['prayer_request'] : null,
            'visited_on' => now()->toDateString(),
        ]);

        if (!empty($data['email'])) {
            $parishName = Setting::get('parish_name', 'St. Ferdinand Catholic Church');

            try {
                Mail::raw("Thank you for visiting {$parishName}!\n\nWe were blessed to have you worship with us. We hope to see you again soon.\n\nIf you have any questions about our parish or would like to learn more about our community, please don't hesitate to contact us.\n\nGod bless you.", function ($message) use ($data, $parishName) {
                    $message->to($data['email'])
                        ->subject("Thank You for Visiting {$parishName}");
                });
            } catch (\Exception $e) {
                // Continue even if email fails
            }
        }

        return $this->success(['visitor_id' => $visitor->id], 'Visitor card submitted successfully', 201);
    }

    /**
     * Get upcoming public events (next 30 days).
     */
    public function events(Request $request): JsonResponse
    {
        $events = Event::where('start_datetime', '>=', now())
            ->where('start_datetime', '<=', now()->addDays(30))
            ->orderBy('start_datetime')
            ->get();

        $publicEvents = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'type' => $event->type,
                'description' => $event->description,
                'start_datetime' => $event->start_datetime?->toIso8601String(),
                'end_datetime' => $event->end_datetime?->toIso8601String(),
                'location' => $event->location,
                'requires_registration' => $event->requires_registration,
            ];
        });

        return $this->success($publicEvents);
    }
}
