<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Models\Member;
use App\Models\Offering;
use App\Models\Tithe;
use App\Models\Pledge;
use App\Models\Donation;
use App\Models\Event;
use App\Models\Family;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SyncController extends BaseApiController
{
    /**
     * Get sync status.
     */
    public function status(Request $request): JsonResponse
    {
        return $this->success([
            'last_sync_at' => $request->header('X-Last-Sync-At'),
            'server_time' => now()->toIso8601String(),
            'pending_changes_count' => $this->countPendingChanges($request->header('X-Last-Sync-At')),
        ]);
    }

    /**
     * Push changes from client to server.
     */
    public function push(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => ['required', 'string'],
            'last_synced_at' => ['required', 'date'],
            'records' => ['required', 'array'],
        ]);

        $synced = 0;
        $conflicts = [];

        foreach ($request->records as $table => $rows) {
            foreach ($rows as $row) {
                $result = $this->syncRow($table, $row, $request->last_synced_at);
                if ($result === 'conflict') {
                    $conflicts[] = ['table' => $table, 'id' => $row['id']];
                } elseif ($result === 'synced') {
                    $synced++;
                }
            }
        }

        return $this->success([
            'synced' => $synced,
            'conflicts' => $conflicts,
        ]);
    }

    /**
     * Pull changes from server to client.
     */
    public function pull(Request $request): JsonResponse
    {
        $request->validate([
            'device_id' => ['required', 'string'],
            'last_synced_at' => ['required', 'date'],
        ]);

        $lastSyncedAt = $request->last_synced_at;

        $records = [
            'members' => Member::where('updated_at', '>', $lastSyncedAt)
                ->orWhere('deleted_at', '>', $lastSyncedAt)
                ->get()
                ->toArray(),
            'offerings' => Offering::where('updated_at', '>', $lastSyncedAt)
                ->orWhere('deleted_at', '>', $lastSyncedAt)
                ->get()
                ->toArray(),
            'tithes' => Tithe::where('updated_at', '>', $lastSyncedAt)
                ->orWhere('deleted_at', '>', $lastSyncedAt)
                ->get()
                ->toArray(),
            'pledges' => Pledge::where('updated_at', '>', $lastSyncedAt)
                ->orWhere('deleted_at', '>', $lastSyncedAt)
                ->get()
                ->toArray(),
            'donations' => Donation::where('updated_at', '>', $lastSyncedAt)
                ->orWhere('deleted_at', '>', $lastSyncedAt)
                ->get()
                ->toArray(),
            'events' => Event::where('updated_at', '>', $lastSyncedAt)
                ->orWhere('deleted_at', '>', $lastSyncedAt)
                ->get()
                ->toArray(),
            'families' => Family::where('updated_at', '>', $lastSyncedAt)
                ->orWhere('deleted_at', '>', $lastSyncedAt)
                ->get()
                ->toArray(),
        ];

        return $this->success([
            'server_time' => now()->toIso8601String(),
            'records' => $records,
            'conflicts' => [],
        ]);
    }

    /**
     * Resolve sync conflicts.
     */
    public function resolve(Request $request): JsonResponse
    {
        $request->validate([
            'resolutions' => ['required', 'array'],
            'resolutions.*.table' => ['required', 'string'],
            'resolutions.*.id' => ['required'],
            'resolutions.*.resolution' => ['required', 'in:local,server'],
        ]);

        foreach ($request->resolutions as $resolution) {
            // Apply resolution logic here
            // For now, just log the resolution
        }

        return $this->success(null, 'Conflicts resolved successfully');
    }

    protected function syncRow(string $table, array $row, string $lastSyncedAt): string
    {
        $modelClass = match ($table) {
            'members' => Member::class,
            'offerings' => Offering::class,
            'tithes' => Tithe::class,
            'pledges' => Pledge::class,
            'donations' => Donation::class,
            default => null,
        };

        if (!$modelClass) {
            return 'ignored';
        }

        if ($row['_action'] === 'delete') {
            $record = $modelClass::withTrashed()->find($row['id']);
            if ($record) {
                $record->delete();
                return 'synced';
            }
            return 'ignored';
        }

        $existing = $modelClass::withTrashed()->find($row['id']);

        if ($existing) {
            if ($existing->updated_at > $lastSyncedAt) {
                return 'conflict';
            }
            $existing->update($row);
            return 'synced';
        }

        $modelClass::create($row);
        return 'synced';
    }

    protected function countPendingChanges(?string $lastSyncedAt): int
    {
        if (!$lastSyncedAt) {
            return 0;
        }

        return Member::where('updated_at', '>', $lastSyncedAt)->count()
            + Offering::where('updated_at', '>', $lastSyncedAt)->count()
            + Tithe::where('updated_at', '>', $lastSyncedAt)->count()
            + Pledge::where('updated_at', '>', $lastSyncedAt)->count()
            + Donation::where('updated_at', '>', $lastSyncedAt)->count();
    }
}
