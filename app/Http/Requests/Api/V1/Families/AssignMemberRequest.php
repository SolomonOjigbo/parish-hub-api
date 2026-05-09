<?php

namespace App\Http\Requests\Api\V1\Families;

use App\Http\Requests\Api\V1\BaseFormRequest;

class AssignMemberRequest extends BaseFormRequest
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
            'member_id'      => ['required', 'exists:members,id'],
            'is_family_head' => ['nullable', 'boolean'],
        ];
    }
}
