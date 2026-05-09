<?php

namespace App\Http\Requests\Api\V1\Committees;

use App\Http\Requests\Api\V1\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateCommitteeRequest extends BaseFormRequest
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
        $id = $this->route('committee') ?? $this->route('id');

        return [
            'name'            => ['sometimes', 'required', 'string', 'max:150', Rule::unique('committees', 'name')->ignore($id)],
            'description'     => ['nullable', 'string'],
            'chair_member_id' => ['nullable', 'exists:members,id'],
            'is_active'       => ['nullable', 'boolean'],
        ];
    }
}
