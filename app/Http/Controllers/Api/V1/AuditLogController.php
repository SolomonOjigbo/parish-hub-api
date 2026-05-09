<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends BaseApiController
{
    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('audit.view');

        $query = AuditLog::with('user');

        if ($request->has('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->to);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return $this->paginated(
            $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user_name' => $log->user?->name ?? 'System',
                    'auditable_type' => $log->auditable_type,
                    'auditable_id' => $log->auditable_id,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            }),
            paginator_meta($logs),
        );
    }
}
