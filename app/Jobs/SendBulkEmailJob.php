<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\CommunicationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendBulkEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $memberIds;
    protected string $subject;
    protected string $message;
    protected int $sentByUserId;
    protected CommunicationLog $log;

    public function __construct(array $memberIds, string $subject, string $message, int $sentByUserId, CommunicationLog $log)
    {
        $this->memberIds = $memberIds;
        $this->subject = $subject;
        $this->message = $message;
        $this->sentByUserId = $sentByUserId;
        $this->log = $log;
    }

    public function handle(): void
    {
        $members = Member::whereIn('id', $this->memberIds)
            ->where('is_active', true)
            ->whereNotNull('email')
            ->get();

        $sentCount = 0;
        $failedCount = 0;
        $errors = [];

        foreach ($members as $member) {
            try {
                $personalizedMessage = str_replace('{first_name}', $member->first_name, $this->message);

                Mail::raw($personalizedMessage, function ($message) use ($member) {
                    $message->to($member->email)
                        ->subject($this->subject);
                });

                $sentCount++;
            } catch (\Exception $e) {
                $failedCount++;
                $errors[] = [
                    'member_id' => $member->id,
                    'email' => $member->email,
                    'error' => $e->getMessage(),
                ];
                Log::error('Email sending failed', [
                    'member_id' => $member->id,
                    'email' => $member->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->log->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => $failedCount === 0 ? 'sent' : ($sentCount > 0 ? 'partial' : 'failed'),
            'provider_response' => json_encode(['errors' => $errors]),
        ]);
    }
}
