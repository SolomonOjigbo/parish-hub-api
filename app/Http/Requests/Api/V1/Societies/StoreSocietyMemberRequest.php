<?php

namespace App\Http\Requests\Api\V1\Societies;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StoreSocietyMemberRequest extends BaseFormRequest
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
            'member_id' => ['required', 'exists:members,id'],
            'role'      => ['nullable', 'in:member,president,vicePresident,secretary,treasurer,PRO,welfareOfficer'],
            'joined_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
