<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pitch_id'       => ['required', 'exists:pitches,id'],
            'customer_name'  => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'booking_date'   => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time'     => ['required', 'date_format:H:i'],
            'end_time'       => ['required', 'date_format:H:i', 'after:start_time'],

            // قواعد الدفع
            'payment_method' => ['required', 'in:manual,online'],
            'payment_option' => ['required', 'in:full,deposit'],
            'deposit_amount' => ['required_if:payment_option,deposit', 'nullable', 'numeric', 'min:0'],
        ];
    }
}
