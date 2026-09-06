<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Donation;
use App\Models\Event;
use App\Models\Member;
use App\Models\Offering;
use App\Models\Pledge;
use App\Models\Society;
use App\Models\Tithe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * One-call dashboard summary. Sections are gated by the caller's
 * permissions: finance figures are omitted without finance.view, etc.
 */
class DashboardController extends BaseApiController
{
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = [];

        if ($user->can('members.view')) {
            $data['member_count'] = Member::count();
            $data['new_members_this_month'] = Member::whereBetween(
                'date_joined',
                [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]
            )->count();

            $weekStart = now()->startOfWeek();
            $weekEnd = now()->endOfWeek();
            $data['birthdays_this_week'] = Member::where('status', 'active')
                ->with('contactDetail')
                ->whereNotNull('date_of_birth')
                ->get()
                ->filter(function (Member $m) use ($weekStart, $weekEnd): bool {
                    return $m->date_of_birth->copy()->year(now()->year)->between($weekStart, $weekEnd);
                })
                ->take(8)
                ->map(fn(Member $m) => [
                    'id' => $m->id,
                    'full_name' => $m->full_name,
                    'primary_phone' => $m->contactDetail?->primary_phone,
                    'photo_url' => $m->photo_url,
                    'date_of_birth' => $m->date_of_birth->format('Y-m-d'),
                ])
                ->values();

            $data['recent_members'] = Member::with(['societies'])
                ->orderByDesc('date_joined')
                ->take(5)
                ->get()
                ->map(fn(Member $m) => [
                    'id' => $m->id,
                    'full_name' => $m->full_name,
                    'photo_url' => $m->photo_url,
                    'status' => $m->status,
                    'society' => $m->societies->first()?->short_name ?? $m->societies->first()?->name,
                ]);

            $data['society_activity'] = Society::withCount('members')
                ->orderByDesc('members_count')
                ->get()
                ->map(fn(Society $s) => [
                    'name' => $s->short_name ?? $s->name,
                    'member_count' => $s->members_count,
                ]);
        }

        if ($user->can('events.view')) {
            $upcoming = Event::withCount('registrations')
                ->where('start_datetime', '>=', now())
                ->orderBy('start_datetime')
                ->take(5)
                ->get();

            $data['upcoming_events'] = $upcoming->map(fn(Event $e) => [
                'id' => $e->id,
                'title' => $e->title,
                'start_datetime' => $e->start_datetime->toIso8601String(),
                'location' => $e->location,
                'registration_count' => $e->registrations_count,
            ]);
            $data['events_this_week_count'] = Event::where('start_datetime', '>=', now())
                ->where('start_datetime', '<=', now()->endOfWeek())
                ->count();
        }

        if ($user->can('finance.view')) {
            $lastSunday = now()->startOfDay();
            while (!$lastSunday->isSunday()) {
                $lastSunday->subDay();
            }

            $data['sunday_offering'] = [
                'date' => $lastSunday->toDateString(),
                'total' => (float) Offering::whereDate('collection_date', $lastSunday->toDateString())->sum('amount'),
            ];

            $data['outstanding_pledges_total'] = (float) Pledge::whereIn('status', ['active', 'overdue'])
                ->get()
                ->sum(fn(Pledge $p) => max(0, (float) $p->total_amount - (float) $p->amount_paid));

            $since = now()->subDays(90)->toDateString();
            $trend = fn($rows) => $rows
                ->groupBy(fn($r) => $r['date'])
                ->map(fn($group, $date) => ['date' => $date, 'total' => (float) collect($group)->sum('amount')])
                ->sortKeys()
                ->values();

            $data['giving_trend'] = [
                'offerings' => $trend(
                    Offering::whereDate('collection_date', '>=', $since)->get()
                        ->map(fn($o) => ['date' => $o->collection_date->toDateString(), 'amount' => $o->amount])
                ),
                'tithes' => $trend(
                    Tithe::whereDate('payment_date', '>=', $since)->get()
                        ->map(fn($t) => ['date' => $t->payment_date->toDateString(), 'amount' => $t->amount])
                ),
                'donations' => $trend(
                    Donation::whereDate('donation_date', '>=', $since)->get()
                        ->map(fn($d) => ['date' => $d->donation_date->toDateString(), 'amount' => $d->amount])
                ),
            ];
        }

        return $this->success($data, 'Dashboard summary retrieved successfully.');
    }
}
