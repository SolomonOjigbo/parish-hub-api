<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Event;
use App\Models\Member;
use App\Models\Pledge;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Derived in-app notifications for the staff topbar/panel.
 * Nothing is persisted — each section is computed from live data and
 * included only when the user holds the matching permission.
 */
class NotificationController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = collect();

        if ($user->can('members.view')) {
            $weekStart = now()->startOfWeek();
            $weekEnd = now()->endOfWeek();

            $birthdays = Member::where('status', 'active')
                ->whereNotNull('date_of_birth')
                ->get()
                ->filter(function (Member $m) use ($weekStart, $weekEnd): bool {
                    $birthday = $m->date_of_birth->copy()->year(now()->year);

                    return $birthday->between($weekStart, $weekEnd);
                })
                ->take(5)
                ->map(fn(Member $m) => [
                    'id' => "birthday-{$m->id}",
                    'title' => 'Birthday this week',
                    'body' => "{$m->full_name} celebrates on " . $m->date_of_birth->copy()->year(now()->year)->format('D, d M'),
                    'time' => now()->startOfDay()->toIso8601String(),
                    'read' => false,
                    'kind' => 'member',
                ]);

            $recentMembers = Member::where('created_at', '>=', now()->subDays(7))
                ->orderByDesc('created_at')
                ->take(5)
                ->get()
                ->map(fn(Member $m) => [
                    'id' => "new-member-{$m->id}",
                    'title' => $m->status === 'inactive' ? 'Registration pending review' : 'New member registered',
                    'body' => "{$m->full_name} ({$m->membership_number})",
                    'time' => $m->created_at->toIso8601String(),
                    'read' => false,
                    'kind' => 'member',
                ]);

            $notifications = $notifications->concat($birthdays)->concat($recentMembers);
        }

        if ($user->can('finance.view')) {
            $overdue = Pledge::with('member')
                ->where(function ($q): void {
                    $q->where('status', 'overdue')
                        ->orWhere(function ($inner): void {
                            $inner->where('status', 'active')
                                ->whereNotNull('end_date')
                                ->whereDate('end_date', '<', now()->toDateString());
                        });
                })
                ->orderBy('end_date')
                ->take(5)
                ->get()
                ->map(fn(Pledge $p) => [
                    'id' => "pledge-{$p->id}",
                    'title' => 'Pledge overdue',
                    'body' => ($p->member?->full_name ?? 'A member') . " — ₦" . number_format((float) $p->balance, 2) . " outstanding on {$p->purpose}",
                    'time' => ($p->end_date ?? $p->updated_at)->toIso8601String(),
                    'read' => false,
                    'kind' => 'pledge',
                ]);

            $notifications = $notifications->concat($overdue);
        }

        if ($user->can('events.view')) {
            $upcoming = Event::where('start_datetime', '>=', now())
                ->where('start_datetime', '<=', now()->addDays(7))
                ->orderBy('start_datetime')
                ->take(5)
                ->get()
                ->map(fn(Event $e) => [
                    'id' => "event-{$e->id}",
                    'title' => 'Upcoming event',
                    'body' => "{$e->title} — " . $e->start_datetime->format('D, d M g:i A'),
                    'time' => now()->startOfDay()->toIso8601String(),
                    'read' => false,
                    'kind' => 'event',
                ]);

            $notifications = $notifications->concat($upcoming);
        }

        return $this->success(
            $notifications->sortByDesc('time')->values(),
            'Notifications retrieved successfully.'
        );
    }
}
