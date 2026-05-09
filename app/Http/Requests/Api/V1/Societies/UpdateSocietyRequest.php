<?php

namespace App\Http\Requests\Api\V1\Societies;

use App\Http\Requests\Api\V1\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateSocietyRequest extends BaseFormRequest
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
        $id = $this->route('society') ?? $this->route('id');

        return [
            'name'        => ['sometimes', 'required', 'string', 'max:150', Rule::unique('societies', 'name')->ignore($id)],
            'slug'        => ['nullable', 'string', 'max:150', Rule::unique('societies', 'slug')->ignore($id)],
            'description' => ['nullable', 'string'],
            'colour'      => ['nullable', 'string', 'max:20'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
