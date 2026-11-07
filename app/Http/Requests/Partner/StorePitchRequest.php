<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class StorePitchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'sport_type'           => ['required', 'string', 'max:50'],
            'court_size'           => ['nullable', 'string', 'max:50'],
            'surface_type'         => ['nullable', 'string', 'max:50'],
            'is_indoor'            => ['nullable', 'boolean'],
            'manual_day_price'     => ['required', 'numeric', 'min:0'],
            'manual_night_price'   => ['required', 'numeric', 'min:0'],
            'online_day_price'     => ['required', 'numeric', 'min:0'],
            'online_night_price'   => ['required', 'numeric', 'min:0'],
            'night_start_at'        => ['required', 'date_format:H:i'],
            'opening_time'          => ['nullable', 'date_format:H:i'],
            'closing_time'          => ['nullable', 'date_format:H:i'],
            'slot_duration_minutes' => ['nullable', 'integer', 'in:30,45,60,90,120'],
            'amenities'             => ['nullable', 'array'],
            'amenities.*'          => ['string', 'max:100'],
            'status'               => ['nullable', 'in:active,maintenance,inactive'],
            // دعم رفع صور وفيديوهات متعددة
            'images'               => ['nullable', 'array'],
            'images.*'             => ['file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // حتى 5 ميجا لكل صورة
            'videos'               => ['nullable', 'array'],
            'videos.*'             => ['file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:51200'], // حتى 50 ميجا لكل فيديو
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'               => 'اسم الملعب مطلوب.',
            'sport_type.required'         => 'نوع الرياضة مطلوب.',
            'manual_day_price.required'   => 'سعر الحجز اليدوي الصباحي مطلوب.',
            'manual_night_price.required' => 'سعر الحجز اليدوي المسائي مطلوب.',
            'online_day_price.required'   => 'سعر الحجز الأونلاين الصباحي مطلوب.',
            'online_night_price.required' => 'سعر الحجز الأونلاين المسائي مطلوب.',
            'night_start_at.required'     => 'وقت بداية الفترة المسائية مطلوب (مثال: 17:00).',
            'night_start_at.date_format'  => 'صيغة وقت بداية الليل يجب أن تكون بتنسيق HH:mm (مثال: 18:00).',
            'images.*.image'              => 'يجب أن يكون الملف صورة صالحة.',
            'videos.*.mimetypes'          => 'يجب أن يكون الفيديو بصيغة مدعومة (MP4, QuickTime, WebM).',
            'videos.*.max'                => 'حجم الفيديو لا يجب أن يتعدى 50 ميجابايت.',
        ];
    }
}
