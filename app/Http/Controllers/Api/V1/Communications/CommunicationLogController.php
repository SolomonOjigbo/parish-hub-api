<?php

namespace App\Http\Controllers\Api\V1\Communications;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\CommunicationLog;
use App\Models\Member;
use App\Resources\Api\V1\CommunicationLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunicationLogController extends BaseApiController
{
    /**
     * Display a listing of communication logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CommunicationLog::with(['sender']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('sent_by')) {
            $query->where('sent_by', $request->sent_by);
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return $this->paginated(
            CommunicationLogResource::collection($logs),
            paginator_meta($logs),
        );
    }

    /**
     * Display the specified communication log.
     */
    public function show(CommunicationLog $log): JsonResponse
    {
        $log->load(['sender', 'recipients']);

        return $this->success(new CommunicationLogResource($log));
    }
}
