<?php

namespace App\Http\Requests\Api\V1\Events;

use App\Http\Requests\Api\V1\BaseFormRequest;

class MarkAttendanceRequest extends BaseFormRequest
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
            'member_ids'   => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:members,id'],
            'member_id'    => ['nullable', 'integer', 'exists:members,id'],
            'action'       => ['nullable', 'in:check_in,remove'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($v): void {
            if (!$this->filled('member_ids') && !$this->filled('member_id')) {
                $v->errors()->add('member_id', 'Either member_ids or member_id is required.');
            }
        });
    }
}
