<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StoreTitheRequest extends BaseFormRequest
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
            'member_id'          => ['required', 'exists:members,id'],
            'period_month'       => ['required', 'integer', 'between:1,12'],
            'period_year'        => ['required', 'integer', 'between:2000,2100'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'payment_method'     => ['required', 'in:cash,bank_transfer,pos,cheque'],
            'transfer_reference' => ['required_if:payment_method,bank_transfer', 'nullable', 'string', 'max:100'],
            'payment_date'       => ['required', 'date'],
            'notes'              => ['nullable', 'string'],
        ];
    }
}
