<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommitteeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'description'     => $this->description,
            'chair_member_id' => $this->chair_member_id,
            'is_active'       => (bool) $this->is_active,

            'chair_member' => $this->whenLoaded('chairMember', function (): ?array {
                if (!$this->chairMember) {
                    return null;
                }

                return [
                    'id'        => $this->chairMember->id,
                    'full_name' => $this->chairMember->full_name,
                ];
            }),

            'member_count'      => $this->when(isset($this->committee_members_count), $this->committee_members_count),
            'next_due_date'     => $this->when(isset($this->next_due_date), fn() => $this->next_due_date),

            'members' => $this->whenLoaded('committeeMembers', function () {
                return $this->committeeMembers->map(function ($cm): array {
                    return [
                        'id'        => $cm->id,
                        'member_id' => $cm->member_id,
                        'role'      => $cm->role,
                        'full_name' => $cm->member?->full_name,
                    ];
                });
            }),

            'action_items' => CommitteeActionItemResource::collection(
                $this->whenLoaded('actionItems')
            ),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
