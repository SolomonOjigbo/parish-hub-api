<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TitheResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->member_id,
            'period_month' => $this->period_month,
            'period_year' => $this->period_year,
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'transfer_reference' => $this->transfer_reference,
            'payment_date' => $this->payment_date?->toIso8601String(),
            'recorded_by' => $this->recorded_by,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'member' => $this->whenLoaded('member', fn() => new MemberResource($this->member)),
            'recorder' => $this->whenLoaded('recorder', fn() => new UserResource($this->recorder)),
        ];
    }
}
