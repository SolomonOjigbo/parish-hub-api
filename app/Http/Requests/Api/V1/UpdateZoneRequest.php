<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

class UpdateZoneRequest extends BaseFormRequest
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
        $id = $this->route('zone') ?? $this->route('id');

        return [
            'name'        => ['sometimes', 'required', 'string', 'max:150', Rule::unique('zones', 'name')->ignore($id)],
            'description' => ['nullable', 'string'],
        ];
    }
}
