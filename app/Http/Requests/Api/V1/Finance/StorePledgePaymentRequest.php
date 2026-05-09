<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StorePledgePaymentRequest extends BaseFormRequest
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
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'payment_date'       => ['required', 'date'],
            'payment_method'     => ['required', 'in:cash,bank_transfer,pos,cheque'],
            'transfer_reference' => ['nullable', 'string', 'max:100'],
            'notes'              => ['nullable', 'string'],
        ];
    }
}
