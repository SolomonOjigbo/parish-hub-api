<?php

namespace App\Http\Controllers\Api\V1\Communications;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Jobs\SendBulkEmailJob;
use App\Models\CommunicationLog;
use App\Models\Member;
use App\Models\Society;
use App\Models\Zone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class EmailController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:communications.send', only: ['send']),
        ];
    }

    /**
     * Send bulk email.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'recipient_type' => ['required', 'in:all,society,zone,individual'],
            'recipient_ids' => ['nullable', 'array'],
            'recipient_ids.*' => ['exists:members,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'schedule_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
        ]);

        $recipientIds = $this->resolveRecipientIds($request->input('recipient_type'), $request->input('recipient_ids', []));

        $log = CommunicationLog::create([
            'type' => 'email',
            'recipient_type' => $request->input('recipient_type'),
            'recipient_ids' => $recipientIds,
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'sent_by' => $request->user()->id,
            'status' => 'pending',
            'scheduled_at' => $request->input('schedule_at'),
        ]);

        $job = new SendBulkEmailJob($recipientIds, $request->input('subject'), $request->input('message'), $request->user()->id, $log);

        if ($request->has('schedule_at')) {
            $job->delay($request->input('schedule_at'));
        }

        dispatch($job);

        return $this->success([
            'recipient_count' => count($recipientIds),
            'log_id' => $log->id,
        ], 'Email queued successfully', 201);
    }

    /**
     * Resolve recipient member IDs based on recipient type.
     */
    protected function resolveRecipientIds(string $recipientType, array $recipientIds): array
    {
        return match ($recipientType) {
            'all' => Member::where('status', 'active')->pluck('id')->toArray(),
            'society' => Member::where('status', 'active')
                ->whereHas('societies', function ($query) use ($recipientIds) {
                    $query->whereIn('societies.id', $recipientIds);
                })
                ->pluck('id')
                ->toArray(),
            'zone' => Member::where('status', 'active')
                ->whereIn('zone_id', $recipientIds)
                ->pluck('id')
                ->toArray(),
            'individual' => $recipientIds,
            default => [],
        };
    }
}
