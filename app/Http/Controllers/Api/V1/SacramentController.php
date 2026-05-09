<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\SacramentalRecord;
use App\Resources\Api\V1\SacramentalRecordResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SacramentController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:members.view'),
        ];
    }

    /**
     * GET /api/v1/sacraments
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 25);

        $paginator = SacramentalRecord::with('member')
            ->when($request->filled('type'), fn($q) => $q->where('type', $request->query('type')))
            ->when($request->filled('from'), fn($q) => $q->whereDate('date', '>=', $request->query('from')))
            ->when($request->filled('to'),   fn($q) => $q->whereDate('date', '<=', $request->query('to')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $search = '%' . $request->query('search') . '%';
                $q->whereHas('member', function ($m) use ($search): void {
                    $m->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)
                        ->orWhere('other_name', 'like', $search)
                        ->orWhere('membership_number', 'like', $search);
                });
            })
            ->orderByDesc('date')
            ->paginate($perPage);

        $items = $paginator->getCollection()->map(function ($record): array {
            $base = (new SacramentalRecordResource($record))->toArray(request());
            $base['member'] = $record->member ? [
                'id'                => $record->member->id,
                'membership_number' => $record->member->membership_number,
                'full_name'         => $record->member->full_name,
            ] : null;

            return $base;
        });

        return $this->paginated(
            $items,
            [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'Sacraments retrieved successfully.'
        );
    }
}
