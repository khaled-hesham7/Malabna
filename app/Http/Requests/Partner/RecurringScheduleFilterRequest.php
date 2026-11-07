<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class RecurringScheduleFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pitch_id'    => ['nullable', 'integer', 'exists:pitches,id'],
            'day_of_week' => ['nullable', 'integer', 'between:0,6'],
            'status'      => ['nullable', 'in:all,active,paused,cancelled'],
        ];
    }
}
