<?php

namespace App\Jobs;

use App\Models\Member;
use App\Models\CommunicationLog;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $memberIds;
    protected string $message;
    protected int $sentByUserId;
    protected CommunicationLog $log;

    public function __construct(array $memberIds, string $message, int $sentByUserId, CommunicationLog $log)
    {
        $this->memberIds = $memberIds;
        $this->message = $message;
        $this->sentByUserId = $sentByUserId;
        $this->log = $log;
    }

    public function handle(SmsService $smsService): void
    {
        $members = Member::whereIn('id', $this->memberIds)
            ->where('is_active', true)
            ->whereNotNull('phone_number')
            ->get();

        $phoneNumbers = $members->pluck('phone_number')->toArray();

        $result = $smsService->send($phoneNumbers, $this->message);

        $this->log->update([
            'sent_count' => $result['sent'],
            'failed_count' => $result['failed'],
            'status' => $result['failed'] === 0 ? 'sent' : ($result['sent'] > 0 ? 'partial' : 'failed'),
            'provider_response' => json_encode(['errors' => $result['errors'] ?? []]),
        ]);

        if (!empty($result['errors'])) {
            Log::error('SMS sending had errors', ['errors' => $result['errors']]);
        }
    }
}
