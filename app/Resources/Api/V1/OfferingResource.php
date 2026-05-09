<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'collection_date' => $this->collection_date?->toIso8601String(),
            'member_id' => $this->member_id,
            'envelope_number' => $this->envelope_number,
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'transfer_reference' => $this->transfer_reference,
            'is_anonymous' => $this->is_anonymous,
            'recorded_by' => $this->recorded_by,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'member' => $this->whenLoaded('member', fn() => new MemberResource($this->member)),
            'recorder' => $this->whenLoaded('recorder', fn() => new UserResource($this->recorder)),
        ];
    }
}
