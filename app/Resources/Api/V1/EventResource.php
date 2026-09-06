<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'title'                 => $this->title,
            'type'                  => $this->type,
            'description'           => $this->description,
            'start_datetime'        => $this->start_datetime?->toIso8601String(),
            'end_datetime'          => $this->end_datetime?->toIso8601String(),
            'location'              => $this->location,
            'max_capacity'          => $this->max_capacity,
            'requires_registration' => (bool) $this->requires_registration,
            'is_retreat'            => (bool) $this->is_retreat,
            'retreat_fee'           => $this->retreat_fee,
            'accommodation_notes'   => $this->accommodation_notes,
            'include_in_bulletin'   => (bool) $this->include_in_bulletin,
            'status'                => $this->status,

            'registration_count' => $this->whenCounted('registrations'),
            'attendance_count'   => $this->whenCounted('attendances'),

            'registration_member_ids' => $this->whenLoaded('registrations', fn() => $this->registrations->pluck('member_id')),
            'attendance_member_ids'   => $this->whenLoaded('attendances', fn() => $this->attendances->pluck('member_id')),

            'created_by' => $this->created_by,
            'creator'    => $this->whenLoaded('creator', fn() => [
                'id'   => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
