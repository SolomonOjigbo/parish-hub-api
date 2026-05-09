<?php

namespace App\Http\Requests\Api\V1\Societies;

use App\Http\Requests\Api\V1\BaseFormRequest;

class UpdateSocietyMemberRequest extends BaseFormRequest
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
            'role'      => ['sometimes', 'required', 'in:member,president,vicePresident,secretary,treasurer,PRO,welfareOfficer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
