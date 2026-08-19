<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlockPitchSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // يُقيد لاحقاً لصاحب الملعب/الكاشير فقط
    }

    public function rules(): array
    {
        return [
            'pitch_id'     => ['required', 'exists:pitches,id'],
            'block_date'   => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time'   => ['required', 'date_format:H:i:s'],
            'end_time'     => ['required', 'date_format:H:i:s', 'after:start_time'],
            'reason'       => ['nullable', 'string', 'max:255'],
        ];
    }
}
