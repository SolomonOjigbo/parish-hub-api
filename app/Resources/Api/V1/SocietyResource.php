<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocietyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'short_name'   => $this->short_name,
            'slug'         => $this->slug,
            'description'  => $this->description,
            'colour'       => $this->colour,
            'icon'         => $this->icon,
            'is_active'    => (bool) $this->is_active,

            'member_count' => $this->when(isset($this->members_count), $this->members_count),
            'next_meeting' => $this->when(
                $this->relationLoaded('meetings') || isset($this->next_meeting_date),
                fn() => $this->next_meeting_date ?? null
            ),

            'members' => $this->whenLoaded('members', function () {
                return $this->members->map(function ($member): array {
                    return [
                        'id'                => $member->id,
                        'membership_number' => $member->membership_number,
                        'full_name'         => $member->full_name,
                        'pivot'             => [
                            'role'      => $member->pivot->role ?? null,
                            'joined_at' => $member->pivot->joined_at ?? null,
                            'is_active' => isset($member->pivot->is_active)
                                ? (bool) $member->pivot->is_active
                                : null,
                        ],
                    ];
                });
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
