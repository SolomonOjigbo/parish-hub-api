<?php

namespace App\Http\Requests\Api\V1\Families;

use App\Http\Requests\Api\V1\BaseFormRequest;

class UpdateFamilyRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name'           => ['sometimes', 'required', 'string', 'max:150'],
            'head_member_id' => ['nullable', 'exists:members,id'],
            'zone_id'        => ['nullable', 'exists:zones,id'],
            'notes'          => ['nullable', 'string'],
        ];
    }
}
