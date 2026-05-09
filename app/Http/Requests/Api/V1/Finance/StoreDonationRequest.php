<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StoreDonationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'donor_name' => ['nullable', 'string', 'max:255'],
            'member_id' => ['nullable', 'exists:members,id'],
            'is_anonymous' => ['boolean'],
            'amount' => ['required', 'numeric', 'min:0'],
            'purpose' => ['required', 'string', 'max:255'],
            'donation_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank_transfer,pos,cheque'],
            'transfer_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
