<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePitchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => ['sometimes', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'sport_type'           => ['sometimes', 'string', 'max:50'],
            'court_size'           => ['nullable', 'string', 'max:50'],
            'surface_type'         => ['nullable', 'string', 'max:50'],
            'is_indoor'            => ['nullable', 'boolean'],
            'manual_day_price'     => ['sometimes', 'numeric', 'min:0'],
            'manual_night_price'   => ['sometimes', 'numeric', 'min:0'],
            'online_day_price'     => ['sometimes', 'numeric', 'min:0'],
            'online_night_price'   => ['sometimes', 'numeric', 'min:0'],
            'night_start_at'       => ['sometimes', 'date_format:H:i', 'nullable'],
            'amenities'            => ['nullable', 'array'],
            'amenities.*'          => ['string', 'max:100'],
            'status'               => ['nullable', 'in:active,maintenance,inactive'],
            // دعم رفع صور وفيديوهات إضافية أو استبدال
            'replace_images'       => ['nullable', 'boolean'],
            'replace_videos'       => ['nullable', 'boolean'],
            'images'               => ['nullable', 'array'],
            'images.*'             => ['file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'videos'               => ['nullable', 'array'],
            'videos.*'             => ['file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:51200'],
            // إمكانية حذف وسائط معينة بواسطة الـ IDs
            'delete_media_ids'     => ['nullable', 'array'],
            'delete_media_ids.*'   => ['integer'],
        ];
    }
}
