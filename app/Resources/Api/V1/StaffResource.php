<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'member_id' => $this->member_id,
            'job_title' => $this->job_title,
            'employment_type' => $this->employment_type,
            'start_date' => $this->start_date?->toIso8601String(),
            'annual_leave_days' => $this->annual_leave_days,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'is_active' => $this->user->is_active,
                'roles' => $this->whenLoaded('user.roles', fn() => $this->user->roles->pluck('name')),
            ]),
            'member' => $this->whenLoaded('member', fn() => new MemberResource($this->member)),
        ];
    }
}
