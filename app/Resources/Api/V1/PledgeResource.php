<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PledgeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'purpose' => $this->purpose,
            'description' => $this->description,
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) $this->amount_paid,
            'payment_frequency' => $this->payment_frequency,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),
            'status' => $this->status,
            'recorded_by' => $this->recorded_by,
            'balance' => (float) $this->balance,
            'completion_percentage' => (float) $this->completion_percentage,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'member' => $this->whenLoaded('member', fn() => new MemberResource($this->member)),
            'recorder' => $this->whenLoaded('recorder', fn() => new UserResource($this->recorder)),
            'payments' => PledgePaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
