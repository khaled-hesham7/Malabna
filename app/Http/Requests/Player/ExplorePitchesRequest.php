<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class ExplorePitchesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // إحداثيات الموقع (GPS)
            'latitude'     => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'    => ['nullable', 'numeric', 'between:-180,180'],
            'max_distance' => ['nullable', 'numeric', 'min:1', 'max:200'], // بالكيلومتر

            // فلتر الميعاد والتوقيت
            'date'         => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time'         => ['nullable', 'date_format:H:i'],
            'time_from'    => ['nullable', 'date_format:H:i'],
            'time_to'      => ['nullable', 'date_format:H:i', 'after:time_from'],

            // فلاتر الملعب والرياضة
            'sport_type'   => ['nullable', 'string', 'max:50'],
            'court_size'   => ['nullable', 'string', 'max:50'],
            'surface_type' => ['nullable', 'string', 'max:50'],
            'is_indoor'    => ['nullable', 'boolean'],
            'city'         => ['nullable', 'string', 'max:100'],
            'max_price'    => ['nullable', 'numeric', 'min:0'],
            'search'       => ['nullable', 'string', 'max:100'],

            // الترقيم والتنقل
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between'           => 'إحداثيات خط العرض (latitude) غير صحيحة.',
            'longitude.between'          => 'إحداثيات خط الطول (longitude) غير صحيحة.',
            'date.date_format'           => 'صيغة التاريخ يجب أن تكون YYYY-MM-DD.',
            'date.after_or_equal'        => 'تاريخ البحث يجب أن يكون اليوم أو في المستقبل.',
            'time.date_format'           => 'صيغة الوقت يجب أن تكون HH:mm (مثال: 20:00).',
            'time_from.date_format'      => 'صيغة وقت البداية يجب أن تكون HH:mm.',
            'time_to.date_format'        => 'صيغة وقت النهاية يجب أن تكون HH:mm.',
            'time_to.after'              => 'وقت نهاية النطاق الزمني يجب أن يكون بعد وقت البداية.',
            'max_distance.numeric'       => 'أقصى مسافة يجب أن تكون رقماً بالكيلومتر.',
            'max_price.numeric'          => 'أقصى سعر يجب أن يكون رقماً.',
        ];
    }
}

