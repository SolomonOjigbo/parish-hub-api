<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PledgePaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pledge_id' => $this->pledge_id,
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date?->toIso8601String(),
            'payment_method' => $this->payment_method,
            'transfer_reference' => $this->transfer_reference,
            'recorded_by' => $this->recorded_by,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'pledge' => $this->whenLoaded('pledge', fn() => new PledgeResource($this->pledge)),
            'recorder' => $this->whenLoaded('recorder', fn() => new UserResource($this->recorder)),
        ];
    }
}
