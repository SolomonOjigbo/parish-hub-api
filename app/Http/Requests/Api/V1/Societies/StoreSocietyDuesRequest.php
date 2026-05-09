<?php

namespace App\Http\Requests\Api\V1\Societies;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StoreSocietyDuesRequest extends BaseFormRequest
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
            'member_id'    => ['required', 'exists:members,id'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year'  => ['required', 'integer', 'between:2000,2100'],
            'amount'       => ['required', 'numeric', 'min:0'],
            'paid_at'      => ['nullable', 'date'],
        ];
    }
}
