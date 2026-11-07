<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class CancelBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason'         => ['nullable', 'string', 'max:255'],
            'refund_deposit' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'سبب الإلغاء لا يجب أن يتجاوز 255 حرف.',
        ];
    }
}
