<?php

namespace App\Jobs;

use App\Mail\EventReminderMail;
use App\Models\CommunicationLog;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EventReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $eventId)
    {
    }

    public function handle(): void
    {
        $event = Event::with('registrations.member.contactDetail')->find($this->eventId);

        if (!$event) {
            return;
        }

        $sent       = 0;
        $recipients = [];

        foreach ($event->registrations as $registration) {
            $member = $registration->member;
            $email  = $member?->contactDetail?->email;

            if (!$member || !$email) {
                continue;
            }

            Mail::to($email)->queue(new EventReminderMail($event, $member));
            $recipients[] = $member->id;
            $sent++;
        }

        CommunicationLog::create([
            'type'             => 'email',
            'subject'          => 'Reminder: ' . $event->title,
            'message'          => 'Auto-reminder dispatched to event registrants.',
            'recipient_type'   => 'individual',
            'recipient_ids'    => $recipients,
            'recipient_count'  => $sent,
            'sent_count'       => $sent,
            'failed_count'     => 0,
            'sent_by'          => $event->created_by,
            'status'           => 'sent',
            'sent_at'          => now(),
        ]);
    }
}
