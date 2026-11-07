<?php

namespace App\Http\Requests\Partner;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pitch_id'       => ['required', 'integer', 'exists:pitches,id'],
            'customer_name'  => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'day_of_week'    => ['required', 'integer', 'between:0,6'], // 0: الأحد إلى 6: السبت
            'start_time'     => ['required', 'date_format:H:i'],
            'end_time'       => ['nullable', 'date_format:H:i'],
            'start_date'     => ['nullable', 'date_format:Y-m-d'],
            'end_date'       => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'], // null يعني حجز ثابت مفتوح ومستمر للأبد
            'notes'          => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'pitch_id.required'          => 'الملعب مطلوب.',
            'pitch_id.exists'            => 'الملعب المحدد غير موجود.',
            'customer_name.required'     => 'اسم العميل مطلوب.',
            'customer_phone.required'    => 'رقم هاتف العميل مطلوب.',
            'day_of_week.required'       => 'يوم الأسبوع مطلوب (0 للأحد حتى 6 للسبت).',
            'day_of_week.between'        => 'يوم الأسبوع يجب أن يكون بين 0 و 6.',
            'start_time.required'        => 'وقت بداية الحجز مطلوب (تنسيق HH:mm مثلاً 20:00).',
            'start_time.date_format'     => 'وقت البداية يجب أن يكون بتنسيق HH:mm.',
            'end_time.date_format'       => 'وقت النهاية يجب أن يكون بتنسيق HH:mm.',
            'start_date.date_format'     => 'تاريخ البداية يجب أن يكون بتنسيق YYYY-MM-DD.',
            'end_date.date_format'       => 'تاريخ النهاية يجب أن يكون بتنسيق YYYY-MM-DD.',
            'end_date.after_or_equal'    => 'تاريخ النهاية يجب أن يكون مساوياً أو لاحقاً لتاريخ البداية.',
        ];
    }
}
