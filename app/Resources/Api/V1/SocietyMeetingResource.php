<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class SocietyMeetingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'society_id'     => $this->society_id,
            'title'          => $this->title,
            'meeting_date'   => $this->meeting_date?->format('Y-m-d'),
            'meeting_time'   => $this->meeting_time,
            'venue'          => $this->venue,
            'agenda'         => $this->agenda,
            'minutes_path'   => $this->minutes_path,
            'minutes_url'    => $this->minutes_path
                ? Storage::disk('public')->url($this->minutes_path)
                : null,
            'minutes_status' => $this->minutes_status,
            'created_at'     => $this->created_at?->toIso8601String(),
            'updated_at'     => $this->updated_at?->toIso8601String(),
        ];
    }
}
