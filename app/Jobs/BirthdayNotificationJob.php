<?php

namespace App\Jobs;

use App\Models\CommunicationLog;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BirthdayNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Member $member;

    public function __construct(Member $member)
    {
        $this->member = $member;
    }

    public function handle(): void
    {
        try {
            $message = "Dear {$this->member->first_name},\n\nHappy Birthday from St. Ferdinand Catholic Church, Lagos! We pray that God blesses you with joy, peace, and prosperity on this special day and throughout the year.\n\nWith love from your parish family.";

            Mail::raw($message, function ($mail) {
                $mail->to($this->member->email)
                    ->subject('Happy Birthday from St. Ferdinand Parish!');
            });

            CommunicationLog::create([
                'type' => 'email',
                'recipient_type' => 'individual',
                'recipient_ids' => [$this->member->id],
                'subject' => 'Happy Birthday from St. Ferdinand Parish!',
                'message' => $message,
                'sent_by' => 1, // System user
                'status' => 'sent',
                'sent_count' => 1,
                'failed_count' => 0,
            ]);

            Log::info("Birthday email sent to member {$this->member->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send birthday email to member {$this->member->id}", ['error' => $e->getMessage()]);
        }
    }
}
