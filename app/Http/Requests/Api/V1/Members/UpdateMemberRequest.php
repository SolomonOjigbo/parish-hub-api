<?php

namespace App\Http\Requests\Api\V1\Members;

use App\Http\Requests\Api\V1\BaseFormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends BaseFormRequest
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
        $memberId = $this->route('member') ?? $this->route('id');

        return [
            'first_name'      => ['sometimes', 'required', 'string', 'max:100'],
            'last_name'       => ['sometimes', 'required', 'string', 'max:100'],
            'other_name'      => ['nullable', 'string', 'max:100'],
            'baptismal_name'  => ['nullable', 'string', 'max:100'],
            'date_of_birth'   => ['nullable', 'date', 'before:today'],
            'gender'          => ['sometimes', 'required', 'in:male,female'],
            'marital_status'  => ['sometimes', 'required', 'in:single,married,widowed,divorced'],
            'occupation'      => ['nullable', 'string', 'max:150'],
            'family_id'       => ['nullable', 'exists:families,id'],
            'is_family_head'  => ['nullable', 'boolean'],
            'zone_id'         => ['nullable', 'exists:zones,id'],
            'status'          => ['nullable', 'in:active,inactive,transferred,deceased'],
            'date_joined'     => ['nullable', 'date'],
            'notes'           => ['nullable', 'string'],

            'primary_phone'   => ['sometimes', 'required', 'string', 'max:15'],
            'whatsapp_number' => ['nullable', 'string', 'max:15'],
            'email'           => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('member_contact_details', 'email')
                    ->ignore($memberId, 'member_id'),
            ],
            'address_line1'   => ['nullable', 'string', 'max:255'],
            'address_line2'   => ['nullable', 'string', 'max:255'],
            'lga'             => ['nullable', 'string', 'max:100'],

            'society_ids'     => ['nullable', 'array'],
            'society_ids.*'   => ['exists:societies,id'],

            'sacraments'              => ['nullable', 'array'],
            'sacraments.*.type'       => ['required_with:sacraments', 'in:baptism,first_communion,confirmation,marriage,holy_orders'],
            'sacraments.*.date'       => ['nullable', 'date'],
            'sacraments.*.church'     => ['nullable', 'string'],
        ];
    }
}
