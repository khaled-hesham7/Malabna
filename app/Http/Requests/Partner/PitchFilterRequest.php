<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class PitchFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sport_type' => ['nullable', 'string', 'max:50'],
            'status'     => ['nullable', 'in:active,maintenance,inactive'],
        ];
    }
}
