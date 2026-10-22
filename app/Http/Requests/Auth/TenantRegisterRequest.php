<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TenantRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Owner Details
            'owner_name'     => ['required', 'string', 'max:255'],
            'owner_phone'    => ['required', 'string', 'max:20', 'unique:users,phone'],
            'owner_email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],

            // Venue Details
            'name'           => ['required', 'string', 'max:255'],
            'company_name'   => ['nullable', 'string', 'max:255'],
            'city'           => ['required', 'string', 'max:100'],
            'address'        => ['required', 'string'],
            'phone'          => ['required', 'string', 'max:20'],
            'description'    => ['nullable', 'string'],
            'latitude'       => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'      => ['nullable', 'numeric', 'between:-180,180'],

            // Logo or Preview Image
            'logo'           => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],

            // Preferred Plan Choice
            'preferred_plan' => ['nullable', 'string', 'in:subscription_only,commission_only,hybrid'],
        ];
    }

    public function messages(): array
    {
        return [
            'owner_name.required'     => 'The owner name is required.',
            'owner_phone.required'    => 'The owner phone number is required.',
            'owner_phone.unique'      => 'The owner phone number has already been taken.',
            'owner_email.required'    => 'The owner email address is required.',
            'owner_email.unique'      => 'The owner email address has already been taken.',
            'owner_password.required' => 'The owner password is required.',
            'name.required'           => 'The venue name is required.',
            'city.required'           => 'The city is required.',
            'address.required'        => 'The venue address is required.',
            'phone.required'          => 'The venue contact phone is required.',
            'logo.image'              => 'The logo file must be a valid image.',
            'logo.max'                => 'The logo image size must not exceed 5MB.',
        ];
    }
}
