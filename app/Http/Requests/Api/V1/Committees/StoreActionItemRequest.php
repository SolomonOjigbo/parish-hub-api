<?php

namespace App\Http\Requests\Api\V1\Committees;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StoreActionItemRequest extends BaseFormRequest
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
            'title'                 => ['required', 'string', 'max:200'],
            'description'           => ['nullable', 'string'],
            'assigned_to_member_id' => ['nullable', 'exists:members,id'],
            'due_date'              => ['nullable', 'date'],
            'is_completed'          => ['nullable', 'boolean'],
        ];
    }
}
