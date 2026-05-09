<?php

namespace App\Jobs;

use App\Models\CommunicationLog;
use App\Models\Member;
use App\Models\Pledge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PledgeOverdueNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Pledge $pledge;

    public function __construct(Pledge $pledge)
    {
        $this->pledge = $pledge;
    }

    public function handle(): void
    {
        $member = $this->pledge->member;

        if (!$member || !$member->email) {
            Log::warning("Cannot send overdue notification for pledge {$this->pledge->id}: member not found or no email");
            return;
        }

        try {
            $message = "Dear {$member->first_name},\n\nThis is a friendly reminder that your pledge for \"{$this->pledge->purpose}\" is now overdue.\n\nDetails:\n- Total Amount: ₦{$this->pledge->total_amount}\n- Amount Paid: ₦{$this->pledge->amount_paid}\n- Balance: ₦{$this->pledge->balance}\n- Completion: {$this->pledge->completion_percentage}%\n\nPlease complete your payment at your earliest convenience. Thank you for your continued support of St. Ferdinand Catholic Church.\n\nGod bless you.";

            Mail::raw($message, function ($mail) use ($member) {
                $mail->to($member->email)
                    ->subject('Pledge Payment Overdue Reminder');
            });

            CommunicationLog::create([
                'type' => 'email',
                'recipient_type' => 'individual',
                'recipient_ids' => [$member->id],
                'subject' => 'Pledge Payment Overdue Reminder',
                'message' => $message,
                'sent_by' => 1, // System user
                'status' => 'sent',
                'sent_count' => 1,
                'failed_count' => 0,
            ]);

            Log::info("Overdue notification sent for pledge {$this->pledge->id} to member {$member->id}");
        } catch (\Exception $e) {
            Log::error("Failed to send overdue notification for pledge {$this->pledge->id}", ['error' => $e->getMessage()]);
        }
    }
}
