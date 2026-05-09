<?php

namespace App\Http\Requests\Api\V1\Committees;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StoreCommitteeRequest extends BaseFormRequest
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
            'name'            => ['required', 'string', 'max:150', 'unique:committees,name'],
            'description'     => ['nullable', 'string'],
            'chair_member_id' => ['nullable', 'exists:members,id'],
            'is_active'       => ['nullable', 'boolean'],
        ];
    }
}
