<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventAttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'event_id'      => $this->event_id,
            'member_id'     => $this->member_id,
            'checked_in_at' => $this->checked_in_at?->toIso8601String(),
            'recorded_by'   => $this->recorded_by,

            'member' => $this->whenLoaded('member', fn() => [
                'id'                => $this->member->id,
                'membership_number' => $this->member->membership_number,
                'full_name'         => $this->member->full_name,
            ]),

            'event' => $this->whenLoaded('event', fn() => [
                'id'             => $this->event->id,
                'title'          => $this->event->title,
                'start_datetime' => $this->event->start_datetime?->toIso8601String(),
            ]),
        ];
    }
}
