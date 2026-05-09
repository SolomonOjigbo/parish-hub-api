<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberContactDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'member_id'       => $this->member_id,
            'primary_phone'   => $this->primary_phone,
            'whatsapp_number' => $this->whatsapp_number,
            'email'           => $this->email,
            'address_line1'   => $this->address_line1,
            'address_line2'   => $this->address_line2,
            'lga'             => $this->lga,
            'state'           => $this->state,
        ];
    }
}
