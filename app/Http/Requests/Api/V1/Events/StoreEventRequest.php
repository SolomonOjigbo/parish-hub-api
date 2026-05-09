<?php

namespace App\Http\Requests\Api\V1\Events;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StoreEventRequest extends BaseFormRequest
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
            'type'                  => ['required', 'in:mass,society_meeting,retreat,fundraiser,feast_day,diocesan,other'],
            'description'           => ['nullable', 'string'],
            'start_datetime'        => ['required', 'date'],
            'end_datetime'          => ['nullable', 'date', 'after:start_datetime'],
            'location'              => ['nullable', 'string', 'max:200'],
            'max_capacity'          => ['nullable', 'integer', 'min:1'],
            'requires_registration' => ['nullable', 'boolean'],
            'is_retreat'            => ['nullable', 'boolean'],
            'retreat_fee'           => ['required_if:is_retreat,true', 'nullable', 'numeric', 'min:0'],
            'accommodation_notes'   => ['nullable', 'string'],
            'include_in_bulletin'   => ['nullable', 'boolean'],
        ];
    }
}
