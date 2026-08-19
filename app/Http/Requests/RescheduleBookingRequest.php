<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RescheduleBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_id'  => ['required', 'exists:bookings,id'],
            'new_date'    => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time'  => ['required', 'date_format:H:i:s'],
            'end_time'    => ['required', 'date_format:H:i:s', 'after:start_time'],
        ];
    }
}
