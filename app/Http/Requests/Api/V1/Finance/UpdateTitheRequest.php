<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\BaseFormRequest;

class UpdateTitheRequest extends BaseFormRequest
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
            'member_id'          => ['sometimes', 'required', 'exists:members,id'],
            'period_month'       => ['sometimes', 'required', 'integer', 'between:1,12'],
            'period_year'        => ['sometimes', 'required', 'integer', 'between:2000,2100'],
            'amount'             => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'payment_method'     => ['sometimes', 'required', 'in:cash,bank_transfer,pos,cheque'],
            'transfer_reference' => ['nullable', 'string', 'max:100'],
            'payment_date'       => ['sometimes', 'required', 'date'],
            'notes'              => ['nullable', 'string'],
        ];
    }
}
