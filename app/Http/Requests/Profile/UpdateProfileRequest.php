<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'  => ['sometimes', 'required', 'string', 'max:100'],
            'phone' => ['sometimes', 'required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($userId)],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'الاسم مطلوب.',
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.unique'   => 'رقم الهاتف مسجل بالفعل بحساب آخر.',
            'email.required' => 'البريد الإلكتروني مطلوب.',
            'email.email'    => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique'   => 'البريد الإلكتروني مسجل بالفعل بحساب آخر.',
        ];
    }
}

