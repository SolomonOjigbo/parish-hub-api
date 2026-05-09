<?php

namespace App\Http\Requests\Api\V1;

class StoreZoneRequest extends BaseFormRequest
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
            'name'        => ['required', 'string', 'max:150', 'unique:zones,name'],
            'description' => ['nullable', 'string'],
        ];
    }
}
