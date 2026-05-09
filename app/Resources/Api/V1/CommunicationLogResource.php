<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommunicationLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'recipient_type' => $this->recipient_type,
            'recipient_ids' => $this->recipient_ids,
            'subject' => $this->subject,
            'message' => $this->message,
            'sent_by' => $this->sent_by,
            'status' => $this->status,
            'sent_count' => $this->sent_count,
            'failed_count' => $this->failed_count,
            'provider_response' => $this->provider_response,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'sender' => $this->whenLoaded('sender', fn() => new UserResource($this->sender)),
            'recipients' => $this->whenLoaded('recipients'),
        ];
    }
}
