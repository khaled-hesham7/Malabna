<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'phone'    => ['required', 'string', 'max:20', 'unique:users,phone'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => 'The name field is required.',
            'phone.required'    => 'The phone number field is required.',
            'phone.unique'      => 'The phone number has already been taken.',
            'email.required'    => 'The email address field is required.',
            'email.email'       => 'Please provide a valid email address.',
            'email.unique'      => 'The email address has already been taken.',
            'password.required' => 'The password field is required.',
            'password.min'      => 'The password must be at least 8 characters.',
        ];
    }
}
