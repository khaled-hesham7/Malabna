<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_price'          => ['required', 'numeric', 'min:0'],
            'subscription_billing_period' => ['nullable', 'string', 'in:monthly,yearly,custom'],
            'commission_type'             => ['required', 'string', 'in:none,percentage,fixed'],
            'commission_rate'             => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_price.required'          => 'The subscription price is required.',
            'subscription_price.numeric'           => 'The subscription price must be a number.',
            'subscription_price.min'               => 'The subscription price must be at least 0.',
            'subscription_billing_period.required' => 'The subscription billing period is required.',
            'subscription_billing_period.in'       => 'The billing period must be monthly, yearly, or custom.',
            'commission_type.required'             => 'The commission type is required.',
            'commission_type.in'                   => 'The commission type must be none, percentage, or fixed.',
            'commission_rate.required'             => 'The commission rate is required.',
            'commission_rate.numeric'              => 'The commission rate must be a number.',
            'commission_rate.min'                  => 'The commission rate must be at least 0.',
        ];
    }
}
