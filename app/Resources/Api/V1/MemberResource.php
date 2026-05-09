<?php

namespace App\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'membership_number' => $this->membership_number,
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'other_name'        => $this->other_name,
            'baptismal_name'    => $this->baptismal_name,
            'full_name'         => $this->full_name,
            'date_of_birth'     => $this->date_of_birth?->format('Y-m-d'),
            'age'               => $this->age,
            'gender'            => $this->gender,
            'marital_status'    => $this->marital_status,
            'occupation'        => $this->occupation,
            'photo_url'         => $this->photo_url,
            'status'            => $this->status,
            'date_joined'       => $this->date_joined?->format('Y-m-d'),
            'is_family_head'    => (bool) $this->is_family_head,

            'family' => $this->whenLoaded('family', function (): ?array {
                if (!$this->family) {
                    return null;
                }

                return [
                    'id'             => $this->family->id,
                    'name'           => $this->family->name,
                    'head_member_id' => $this->family->head_member_id,
                    'zone_id'        => $this->family->zone_id,
                ];
            }),

            'zone' => new ZoneResource($this->whenLoaded('zone')),

            'contact' => new MemberContactDetailResource($this->whenLoaded('contactDetail')),

            'societies' => $this->whenLoaded('societies', function () {
                return $this->societies->map(function ($society): array {
                    return [
                        'id'        => $society->id,
                        'name'      => $society->name,
                        'slug'      => $society->slug,
                        'colour'    => $society->colour,
                        'icon'      => $society->icon,
                        'is_active' => (bool) $society->is_active,
                        'pivot'     => [
                            'role'      => $society->pivot->role ?? null,
                            'joined_at' => $society->pivot->joined_at ?? null,
                            'is_active' => isset($society->pivot->is_active)
                                ? (bool) $society->pivot->is_active
                                : null,
                        ],
                    ];
                });
            }),

            'sacraments' => SacramentalRecordResource::collection(
                $this->whenLoaded('sacramentalRecords')
            ),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
