<?php

namespace App\Http\Requests\Api\V1\Societies;

use App\Http\Requests\Api\V1\BaseFormRequest;

class StoreSocietyMeetingRequest extends BaseFormRequest
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
            'title'        => ['required', 'string', 'max:200'],
            'meeting_date' => ['required', 'date'],
            'meeting_time' => ['nullable', 'date_format:H:i'],
            'venue'        => ['nullable', 'string', 'max:200'],
            'agenda'       => ['nullable', 'string'],
        ];
    }
}
