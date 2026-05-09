<?php

namespace App\Http\Requests\Api\V1\Finance;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StorePledgeRequest extends BaseFormRequest
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
            'member_id'         => ['required', 'exists:members,id'],
            'purpose'           => ['required', 'string', 'max:200'],
            'description'       => ['nullable', 'string'],
            'total_amount'      => ['required', 'numeric', 'min:1'],
            'payment_frequency' => ['required', 'in:one_off,monthly,custom'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
