<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'phone'         => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'string', 'min:6'],
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
            'password.required'      => 'كلمة المرور مطلوبة.',
            'password.min'           => 'كلمة المرور يجب ألا تقل عن 6 أحرف.',
            'permissions.*.exists'   => 'إحدى الصلاحيات المحددة غير صالحة.',
            'avatar.image'           => 'الملف المرفوع يجب أن يكون صورة صالحة.',
            'avatar.max'             => 'حجم الصورة لا يجب أن يتجاوز 5 ميجابايت.',
        ];
    }
}

