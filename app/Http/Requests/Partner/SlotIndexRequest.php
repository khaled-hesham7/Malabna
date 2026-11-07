<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class SlotIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.date_format' => 'صيغة التاريخ غير صحيحة، يجب أن تكون بتنسيق YYYY-MM-DD.',
        ];
    }
}
