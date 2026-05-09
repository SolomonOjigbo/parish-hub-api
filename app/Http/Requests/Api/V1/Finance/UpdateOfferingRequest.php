<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\BaseFormRequest;

class UpdateOfferingRequest extends BaseFormRequest
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
            'collection_date'     => ['sometimes', 'required', 'date'],
            'member_id'           => ['nullable', 'exists:members,id'],
            'envelope_number'     => ['nullable', 'string', 'max:50'],
            'amount'              => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'payment_method'      => ['sometimes', 'required', 'in:cash,bank_transfer,pos,cheque'],
            'transfer_reference'  => ['nullable', 'string', 'max:100'],
            'is_anonymous'        => ['nullable', 'boolean'],
            'notes'               => ['nullable', 'string'],
        ];
    }
}
