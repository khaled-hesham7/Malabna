<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRecurringScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pitch_id'       => ['required', 'exists:pitches,id'],
            'day_of_week'    => ['required', 'integer', 'between:0,6'], // 0 = Sunday, 6 = Saturday
            'start_time'     => ['required', 'date_format:H:i:s'],
            'end_time'       => ['required', 'date_format:H:i:s', 'after:start_time'],
            'customer_name'  => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'start_date'     => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date'       => ['nullable', 'date', 'date_format:Y-m-d', 'after:start_date'],
        ];
    }
}
