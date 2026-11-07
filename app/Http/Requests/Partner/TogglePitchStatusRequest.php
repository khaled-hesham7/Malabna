<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class TogglePitchStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:active,maintenance,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'الحالة المحددة غير صالحة. الخيارات المتاحة: active, maintenance, inactive.',
        ];
    }
}
