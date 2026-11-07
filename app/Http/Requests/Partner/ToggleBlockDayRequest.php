<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class ToggleBlockDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'       => ['required', 'date_format:Y-m-d'],
            'action'     => ['required', 'in:block,unblock'],
            'block_type' => ['nullable', 'in:all,online'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'       => 'تاريخ اليوم مطلوب.',
            'date.date_format'    => 'صيغة التاريخ غير صحيحة، يجب أن تكون بتنسيق YYYY-MM-DD.',
            'action.required'     => 'الإجراء مطلوب (block أو unblock).',
            'action.in'           => 'الإجراء يجب أن يكون block أو unblock.',
            'block_type.in'       => 'نوع الإغلاق يجب أن يكون all أو online.',
        ];
    }
}
