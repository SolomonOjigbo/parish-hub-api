<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StoreOfferingRequest extends BaseFormRequest
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
            'collection_date'     => ['required', 'date'],
            'member_id'           => ['nullable', 'exists:members,id'],
            'envelope_number'     => ['nullable', 'string', 'max:50'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'payment_method'      => ['required', 'in:cash,bank_transfer,pos,cheque'],
            'transfer_reference'  => ['required_if:payment_method,bank_transfer', 'nullable', 'string', 'max:100'],
            'is_anonymous'        => ['nullable', 'boolean'],
            'notes'               => ['nullable', 'string'],
        ];
    }
}
