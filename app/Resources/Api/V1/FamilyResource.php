<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'head_member_id' => $this->head_member_id,
            'zone_id'        => $this->zone_id,
            'notes'          => $this->notes,

            'zone'    => new ZoneResource($this->whenLoaded('zone')),
            'members' => MemberResource::collection($this->whenLoaded('members')),

            'head_member' => $this->whenLoaded('headMember', function (): ?array {
                if (!$this->headMember) {
                    return null;
                }

                return [
                    'id'                => $this->headMember->id,
                    'membership_number' => $this->headMember->membership_number,
                    'full_name'         => $this->headMember->full_name,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
