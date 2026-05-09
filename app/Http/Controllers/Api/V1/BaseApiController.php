<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BaseApiController extends Controller
{
    use AuthorizesRequests, ApiResponse;

    /**
     * Log an audit entry automatically.
     */
    protected function logAudit(
        string $action,
        Model $model,
        array $oldValues = [],
        array $newValues = []
    ): void {
        AuditLog::create([
            'action' => $action,
            'user_id' => auth()->id(),
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
