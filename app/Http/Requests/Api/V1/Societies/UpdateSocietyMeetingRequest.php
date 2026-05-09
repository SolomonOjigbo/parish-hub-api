<?php

namespace App\Http\Requests\Api\V1\Societies;

use App\Http\Requests\Api\V1\BaseFormRequest;

class UpdateSocietyMeetingRequest extends BaseFormRequest
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
            'title'          => ['sometimes', 'required', 'string', 'max:200'],
            'meeting_date'   => ['sometimes', 'required', 'date'],
            'meeting_time'   => ['nullable', 'date_format:H:i'],
            'venue'          => ['nullable', 'string', 'max:200'],
            'agenda'         => ['nullable', 'string'],
            'minutes_status' => ['nullable', 'in:pending,filed'],
        ];
    }
}
