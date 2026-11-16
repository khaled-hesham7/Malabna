<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'ملف الصورة الشخصية مطلوب.',
            'avatar.image'    => 'الملف يجب أن يكون صورة صالحة.',
            'avatar.mimes'    => 'امتداد الصورة يجب أن يكون: jpeg, png, jpg, webp.',
            'avatar.max'      => 'حجم الصورة لا يجب أن يتجاوز 5 ميجابايت.',
        ];
    }
}

