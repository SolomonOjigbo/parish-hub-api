<?php

namespace App\Http\Controllers\Api\V1\Communications;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Jobs\SendBulkSmsJob;
use App\Models\CommunicationLog;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends BaseApiController
{
    /**
     * Send bulk SMS.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_type' => ['required', 'in:all,society,zone,individual'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['exists:members,id'],
            'message' => ['required', 'string'],
            'schedule_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
        ]);

        $recipientIds = $this->resolveRecipientIds($request->input('recipient_type'), $request->input('recipient_ids', []));

        $log = CommunicationLog::create([
            'type' => 'sms',
            'recipient_type' => $request->input('recipient_type'),
            'recipient_ids' => $recipientIds,
            'subject' => null,
            'message' => $request->input('message'),
            'sent_by' => $request->user()->id,
            'status' => 'pending',
            'scheduled_at' => $request->input('schedule_at'),
        ]);

        $job = new SendBulkSmsJob($recipientIds, $request->input('message'), $request->user()->id, $log);

        if ($request->has('schedule_at')) {
            $job->delay($request->input('schedule_at'));
        }

        dispatch($job);

        return $this->success([
            'recipient_count' => count($recipientIds),
            'log_id' => $log->id,
        ], 'SMS queued successfully', 201);
    }

    /**
     * Resolve recipient member IDs based on recipient type.
     */
    protected function resolveRecipientIds(string $recipientType, array $recipientIds): array
    {
        return match ($recipientType) {
            'all' => Member::where('is_active', true)->pluck('id')->toArray(),
            'society' => Member::where('is_active', true)
                ->whereHas('societies', function ($query) use ($recipientIds) {
                    $query->whereIn('societies.id', $recipientIds);
                })
                ->pluck('id')
                ->toArray(),
            'zone' => Member::where('is_active', true)
                ->whereIn('zone_id', $recipientIds)
                ->pluck('id')
                ->toArray(),
            'individual' => $recipientIds,
            default => [],
        };
    }
}
