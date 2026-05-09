<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventRegistrationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'event_id'       => $this->event_id,
            'member_id'      => $this->member_id,
            'registered_at'  => $this->registered_at?->toIso8601String(),
            'payment_status' => $this->payment_status,
            'amount_paid'    => (float) $this->amount_paid,
            'notes'          => $this->notes,

            'member' => $this->whenLoaded('member', fn() => [
                'id'                => $this->member->id,
                'membership_number' => $this->member->membership_number,
                'full_name'         => $this->member->full_name,
                'phone'             => $this->member->contactDetail?->primary_phone,
                'email'             => $this->member->contactDetail?->email,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
