<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Finance\StoreDonationRequest;
use App\Http\Requests\Api\V1\Finance\UpdateDonationRequest;
use App\Models\Donation;
use App\Resources\Api\V1\DonationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DonationController extends BaseApiController implements HasMiddleware
{
    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.view',   only: ['index', 'show', 'donor', 'receipt']),
            new Middleware('permission:finance.create', only: ['store']),
            new Middleware('permission:finance.edit',   only: ['update']),
            new Middleware('permission:finance.delete', only: ['destroy']),
        ];
    }

    /**
     * GET /api/v1/donations/{donation}/receipt — printable PDF receipt.
     */
    public function receipt(Donation $donation): \Illuminate\Http\Response
    {
        $donation->load('member.contactDetail');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('receipts.donation', [
            'donation'    => $donation,
            'parish_name' => \App\Models\Setting::get('parish_name', 'St. Ferdinand Catholic Church'),
            'diocese'     => \App\Models\Setting::get('diocese', 'Catholic Archdiocese of Lagos'),
            'address'     => \App\Models\Setting::get('parish_address', 'Boys Town, Ipaja, Lagos'),
        ]);

        return $pdf->download("donation-receipt-{$donation->id}.pdf");
    }

    /**
     * Display a listing of donations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Donation::with(['member', 'recorder']);

        if ($request->has('donation_date_from')) {
            $query->where('donation_date', '>=', $request->donation_date_from);
        }

        if ($request->has('donation_date_to')) {
            $query->where('donation_date', '<=', $request->donation_date_to);
        }

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        if ($request->has('purpose')) {
            $query->where('purpose', 'like', '%' . $request->purpose . '%');
        }

        $donations = $query->orderBy('donation_date', 'desc')->paginate($request->input('per_page', 15));

        return $this->paginated(
            DonationResource::collection($donations),
            paginator_meta($donations),
        );
    }

    /**
     * Store a newly created donation.
     */
    public function store(StoreDonationRequest $request): JsonResponse
    {
        $donation = Donation::create(array_merge($request->validated(), [
            'recorded_by' => $request->user()->id,
        ]));

        return $this->success(new DonationResource($donation->load(['member', 'recorder'])), 'Donation created successfully', 201);
    }

    /**
     * Display the specified donation.
     */
    public function show(Donation $donation): JsonResponse
    {
        return $this->success(new DonationResource($donation->load(['member', 'recorder'])));
    }

    /**
     * Update the specified donation.
     */
    public function update(UpdateDonationRequest $request, Donation $donation): JsonResponse
    {
        $donation->update($request->validated());

        return $this->success(new DonationResource($donation->load(['member', 'recorder'])), 'Donation updated successfully');
    }

    /**
     * Remove the specified donation.
     */
    public function destroy(Donation $donation): JsonResponse
    {
        $donation->delete();

        return $this->success(null, 'Donation deleted successfully');
    }

    /**
     * Get donation history for a specific member.
     */
    public function donor(int $memberId, Request $request): JsonResponse
    {
        $donations = Donation::with(['recorder'])
            ->where('member_id', $memberId)
            ->orderBy('donation_date', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->paginated(
            DonationResource::collection($donations),
            paginator_meta($donations),
        );
    }
}
