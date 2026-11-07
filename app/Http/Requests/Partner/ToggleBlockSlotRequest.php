<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class ToggleBlockSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'block_type' => ['nullable', 'in:all,online,unblock'],
        ];
    }

    public function messages(): array
    {
        return [
            'block_type.in' => 'نوع الإغلاق غير صالح. الخيارات المتاحة: all, online, unblock.',
        ];
    }
}
