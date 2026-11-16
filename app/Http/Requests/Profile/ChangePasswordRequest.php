<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'string'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required'      => 'كلمة المرور الحالية مطلوبة.',
            'password.required'              => 'كلمة المرور الجديدة مطلوبة.',
            'password.min'                   => 'كلمة المرور الجديدة يجب ألا تقل عن 8 أحرف.',
            'password.confirmed'             => 'تأكيد كلمة المرور غير متطابق.',
            'password_confirmation.required' => 'تأكيد كلمة المرور مطلوب.',
        ];
    }
}

