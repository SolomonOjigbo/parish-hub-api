<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BulletinResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sunday_date' => $this->sunday_date?->toIso8601String(),
            'title' => $this->title,
            'content' => $this->content,
            'generated_by' => $this->generated_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'generator' => $this->whenLoaded('generator', fn() => new UserResource($this->generator)),
        ];
    }
}
