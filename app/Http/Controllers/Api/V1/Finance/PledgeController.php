<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Finance\StorePledgePaymentRequest;
use App\Http\Requests\Api\V1\Finance\StorePledgeRequest;
use App\Http\Requests\Api\V1\Finance\UpdatePledgeRequest;
use App\Models\Pledge;
use App\Models\PledgePayment;
use App\Resources\Api\V1\PledgePaymentResource;
use App\Resources\Api\V1\PledgeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class PledgeController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.view',   only: ['index', 'show', 'overdue', 'payments']),
            new Middleware('permission:finance.create', only: ['store', 'addPayment']),
            new Middleware('permission:finance.edit',   only: ['update']),
            new Middleware('permission:finance.delete', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of pledges.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pledge::with(['member', 'recorder', 'payments']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        $pledges = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return $this->paginated(
            PledgeResource::collection($pledges),
            paginator_meta($pledges),
        );
    }

    /**
     * Display overdue pledges.
     */
    public function overdue(Request $request): JsonResponse
    {
        $pledges = Pledge::with(['member', 'recorder', 'payments'])
            ->where('status', 'active')
            ->where('end_date', '<', now())
            ->orderBy('end_date', 'asc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated(
            PledgeResource::collection($pledges),
            paginator_meta($pledges),
        );
    }

    /**
     * Store a newly created pledge.
     */
    public function store(StorePledgeRequest $request): JsonResponse
    {
        $pledge = Pledge::create(array_merge($request->validated(), [
            'recorded_by' => $request->user()->id,
        ]));

        return $this->success(new PledgeResource($pledge->load(['member', 'recorder', 'payments'])), 'Pledge created successfully', 201);
    }

    /**
     * Display the specified pledge.
     */
    public function show(Pledge $pledge): JsonResponse
    {
        return $this->success(new PledgeResource($pledge->load(['member', 'recorder', 'payments'])));
    }

    /**
     * Update the specified pledge.
     */
    public function update(UpdatePledgeRequest $request, Pledge $pledge): JsonResponse
    {
        $pledge->update($request->validated());

        return $this->success(new PledgeResource($pledge->load(['member', 'recorder', 'payments'])), 'Pledge updated successfully');
    }

    /**
     * Remove the specified pledge.
     */
    public function destroy(Pledge $pledge): JsonResponse
    {
        $pledge->delete();

        return $this->success(null, 'Pledge deleted successfully');
    }

    /**
     * Add a payment to a pledge.
     */
    public function addPayment(StorePledgePaymentRequest $request, Pledge $pledge): JsonResponse
    {
        DB::transaction(function () use ($request, $pledge) {
            $payment = PledgePayment::create(array_merge($request->validated(), [
                'pledge_id' => $pledge->id,
                'recorded_by' => $request->user()->id,
            ]));

            $totalPaid = $pledge->payments()->sum('amount');
            $pledge->amount_paid = $totalPaid;

            if ((float) $totalPaid >= (float) $pledge->total_amount) {
                $pledge->status = 'completed';
            }

            $pledge->save();

            return $payment;
        });

        return $this->success(new PledgeResource($pledge->load(['member', 'recorder', 'payments'])), 'Payment added successfully');
    }

    /**
     * Get all payments for a pledge.
     */
    public function payments(Pledge $pledge, Request $request): JsonResponse
    {
        $payments = $pledge->payments()->with(['recorder'])->orderBy('payment_date', 'desc')->paginate($request->input('per_page', 15));

        return $this->paginated(
            PledgePaymentResource::collection($payments),
            paginator_meta($payments),
        );
    }
}
