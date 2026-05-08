<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            'permissions' => $this->whenLoaded('permissions', fn() => $this->permissions->pluck('name')),
            'member_id' => $this->member_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
