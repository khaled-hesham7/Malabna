<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetAvailableSlotsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Open for guests and cashiers to query pitch availability
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    /**
     * Custom validation messages in English.
     */
    public function messages(): array
    {
        return [
            'date.required'       => 'The booking date is required.',
            'date.date'           => 'The provided date is invalid.',
            'date.date_format'    => 'The date must match the YYYY-MM-DD format.',
            'date.after_or_equal' => 'You cannot query slots for a past date.',
        ];
    }
}
