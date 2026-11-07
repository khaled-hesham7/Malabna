<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSlotPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'manual_price'    => ['required', 'numeric', 'min:0'],
            'online_price'    => ['nullable', 'numeric', 'min:0'],
            'apply_to_future' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'manual_price.required' => 'السعر اليدوي مطلوب.',
            'manual_price.numeric'  => 'السعر اليدوي يجب أن يكون رقماً.',
            'manual_price.min'      => 'السعر اليدوي لا يمكن أن يكون سالباً.',
            'online_price.numeric'  => 'السعر الأونلاين يجب أن يكون رقماً.',
            'online_price.min'      => 'السعر الأونلاين لا يمكن أن يكون سالباً.',
        ];
    }
}
