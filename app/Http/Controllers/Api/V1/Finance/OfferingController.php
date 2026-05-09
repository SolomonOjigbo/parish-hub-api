<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Finance\StoreOfferingRequest;
use App\Http\Requests\Api\V1\Finance\UpdateOfferingRequest;
use App\Imports\OfferingsImport;
use App\Models\Offering;
use App\Resources\Api\V1\OfferingResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class OfferingController extends BaseApiController
{
    /**
     * Display a listing of offerings.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Offering::with(['member', 'recorder']);

        if ($request->has('collection_date_from')) {
            $query->where('collection_date', '>=', $request->collection_date_from);
        }

        if ($request->has('collection_date_to')) {
            $query->where('collection_date', '<=', $request->collection_date_to);
        }

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        $offerings = $query->orderBy('collection_date', 'desc')->paginate($request->input('per_page', 15));

        return $this->paginated(
            OfferingResource::collection($offerings),
            paginator_meta($offerings),
        );
    }

    /**
     * Store a newly created offering.
     */
    public function store(StoreOfferingRequest $request): JsonResponse
    {
        $offering = Offering::create(array_merge($request->validated(), [
            'recorded_by' => $request->user()->id,
        ]));

        return $this->success(new OfferingResource($offering->load(['member', 'recorder'])), 'Offering created successfully', 201);
    }

    /**
     * Display the specified offering.
     */
    public function show(Offering $offering): JsonResponse
    {
        return $this->success(new OfferingResource($offering->load(['member', 'recorder'])));
    }

    /**
     * Update the specified offering.
     */
    public function update(UpdateOfferingRequest $request, Offering $offering): JsonResponse
    {
        $offering->update($request->validated());

        return $this->success(new OfferingResource($offering->load(['member', 'recorder'])), 'Offering updated successfully');
    }

    /**
     * Remove the specified offering.
     */
    public function destroy(Offering $offering): JsonResponse
    {
        $offering->delete();

        return $this->success(null, 'Offering deleted successfully');
    }

    /**
     * Get summary statistics for offerings.
     */
    public function summary(Request $request): JsonResponse
    {
        $query = Offering::query();

        if ($request->has('collection_date_from')) {
            $query->where('collection_date', '>=', $request->collection_date_from);
        }

        if ($request->has('collection_date_to')) {
            $query->where('collection_date', '<=', $request->collection_date_to);
        }

        $totalAmount = $query->sum('amount');
        $totalCount = $query->count();

        return $this->success([
            'total_amount' => (float) $totalAmount,
            'total_count' => $totalCount,
        ]);
    }

    /**
     * Import offerings from Excel file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new OfferingsImport($request->user()->id);

        Excel::import($import, $request->file('file'));

        return $this->success([
            'success_count' => $import->getSuccessCount(),
            'errors' => $import->getErrors(),
        ], 'Offerings imported successfully');
    }
}
