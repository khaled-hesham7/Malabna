<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class PaySubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', 'in:visa,mastercard,vodafone_cash,fawry,bank_transfer'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'طريقة الدفع مطلوبة.',
            'payment_method.in'       => 'طريقة الدفع المحددة غير مدعومة.',
        ];
    }
}
