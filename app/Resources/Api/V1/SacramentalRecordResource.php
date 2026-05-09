<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SacramentalRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'member_id'        => $this->member_id,
            'type'             => $this->type,
            'date'             => $this->date?->format('Y-m-d'),
            'church'           => $this->church,
            'minister'         => $this->minister,
            'spouse_name'      => $this->spouse_name,
            'certificate_path' => $this->certificate_path,
            'notes'            => $this->notes,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
