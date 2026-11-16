<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $staffUser = $this->route('staff') ?? $this->route('user');
        $staffId = $staffUser instanceof \App\Models\User ? $staffUser->id : $staffUser;

        return [
            'name'          => ['sometimes', 'required', 'string', 'max:100'],
            'phone'         => ['sometimes', 'required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($staffId)],
            'email'         => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staffId)],
            'password'      => ['nullable', 'string', 'min:6'],
            'status'        => ['nullable', 'in:active,inactive'],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'avatar'        => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'اسم الموظف مطلوب.',
            'phone.required'         => 'رقم هاتف الموظف مطلوب.',
            'phone.unique'           => 'رقم الهاتف مستخدم بالفعل بحساب آخر.',
            'email.required'         => 'البريد الإلكتروني مطلوب.',
            'email.email'            => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique'           => 'البريد الإلكتروني مستخدم بالفعل.',
            'password.min'           => 'كلمة المرور يجب ألا تقل عن 6 أحرف.',
            'status.in'              => 'الحالة يجب أن تكون إما active أو inactive.',
            'permissions.*.exists'   => 'إحدى الصلاحيات المحددة غير صالحة.',
            'avatar.image'           => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'avatar.max'             => 'حجم الصورة لا يجب أن يتجاوز 5 ميجابايت.',
        ];
    }
}

