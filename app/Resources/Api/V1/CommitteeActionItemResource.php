<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommitteeActionItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'committee_id'          => $this->committee_id,
            'title'                 => $this->title,
            'description'           => $this->description,
            'assigned_to_member_id' => $this->assigned_to_member_id,
            'due_date'              => $this->due_date?->format('Y-m-d'),
            'is_completed'          => (bool) $this->is_completed,
            'completed_at'          => $this->completed_at?->toIso8601String(),

            'assigned_member' => $this->whenLoaded('assignedMember', function (): ?array {
                if (!$this->assignedMember) {
                    return null;
                }

                return [
                    'id'        => $this->assignedMember->id,
                    'full_name' => $this->assignedMember->full_name,
                ];
            }),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
