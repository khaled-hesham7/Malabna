<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecurringScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name'  => ['sometimes', 'required', 'string', 'max:100'],
            'customer_phone' => ['sometimes', 'required', 'string', 'max:20'],
            'end_date'       => ['nullable', 'date_format:Y-m-d'],
            'notes'          => ['nullable', 'string', 'max:500'],
            'status'         => ['sometimes', 'in:active,paused,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_name.required'  => 'اسم العميل مطلوب.',
            'customer_phone.required' => 'رقم هاتف العميل مطلوب.',
            'end_date.date_format'    => 'تاريخ النهاية يجب أن يكون بتنسيق YYYY-MM-DD.',
            'status.in'               => 'الحالة يجب أن تكون active أو paused أو cancelled.',
        ];
    }
}
