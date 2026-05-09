<?php

namespace App\Http\Requests\Api\V1\Societies;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StoreSocietyRequest extends BaseFormRequest
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
            'name'        => ['required', 'string', 'max:150', 'unique:societies,name'],
            'slug'        => ['nullable', 'string', 'max:150', 'unique:societies,slug'],
            'description' => ['nullable', 'string'],
            'colour'      => ['nullable', 'string', 'max:20'],
            'icon'        => ['nullable', 'string', 'max:100'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }
}
