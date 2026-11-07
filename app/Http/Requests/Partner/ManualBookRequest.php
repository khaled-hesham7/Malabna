<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class ManualBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required'  => 'اسم العميل مطلوب.',
            'customer_name.max'       => 'اسم العميل لا يجب أن يتجاوز 100 حرف.',
            'customer_phone.required' => 'رقم هاتف العميل مطلوب.',
            'customer_phone.max'      => 'رقم هاتف العميل لا يجب أن يتجاوز 20 حرف.',
            'deposit_amount.numeric'  => 'مبلغ العربون يجب أن يكون رقماً.',
            'deposit_amount.min'      => 'مبلغ العربون لا يمكن أن يكون سالباً.',
            'notes.max'               => 'الملاحظات لا يجب أن تتجاوز 255 حرف.',
        ];
    }
}
