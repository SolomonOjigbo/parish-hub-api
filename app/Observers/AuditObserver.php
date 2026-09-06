<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Generic audit trail for domain models, writing to the app's own
 * audit_logs table (the single audit store — see AuditLogController).
 * Members are logged inline by MemberController with request context,
 * so this observer is registered for the other key models.
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);
        if (empty($changes)) {
            return;
        }

        $original = array_intersect_key($model->getOriginal(), $changes);
        $this->log($model, 'updated', $original, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', $model->getAttributes(), null);
    }

    private function log(Model $model, string $event, ?array $old, ?array $new): void
    {
        $entity = Str::snake(class_basename($model));

        AuditLog::create([
            'user_id'        => auth()->id(),
            'action'         => "{$entity}.{$event}",
            'auditable_type' => $model::class,
            'auditable_id'   => $model->getKey() ?? 0,
            'old_values'     => $old ? $this->scrub($old) : null,
            'new_values'     => $new ? $this->scrub($new) : null,
            'ip_address'     => request()?->ip(),
            'user_agent'     => request()?->userAgent(),
        ]);
    }

    /** Never persist secrets into the audit trail. */
    private function scrub(array $attributes): array
    {
        unset($attributes['password'], $attributes['remember_token']);

        return $attributes;
    }
}
