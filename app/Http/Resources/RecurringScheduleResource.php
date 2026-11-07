<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringScheduleResource extends JsonResource
{
    /**
     * أسماء أيام الأسبوع بالعربية
     */
    protected static array $dayNames = [
        0 => 'الأحد',
        1 => 'الاثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
        6 => 'السبت',
    ];

    protected static array $dayNamesEn = [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];

    public function toArray(Request $request): array
    {
        $dayOfWeek = (int) $this->day_of_week;
        $startTime = substr($this->start_time, 0, 5);
        $endTime   = substr($this->end_time, 0, 5);

        return [
            'id'             => $this->id,
            'tenant_id'      => $this->tenant_id,
            'pitch_id'       => $this->pitch_id,
            'pitch'          => $this->when($this->relationLoaded('pitch'), function () {
                return [
                    'id'   => $this->pitch->id,
                    'name' => $this->pitch->name,
                ];
            }),
            'customer_name'  => $this->customer_name ?? $this->user?->name,
            'customer_phone' => $this->customer_phone ?? $this->user?->phone,
            'booking_type'   => $this->booking_type,
            'day_of_week'    => $dayOfWeek,
            'day_name'       => self::$dayNames[$dayOfWeek] ?? '',
            'day_name_en'    => self::$dayNamesEn[$dayOfWeek] ?? '',
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'time_label'     => "{$startTime} - {$endTime}",
            'start_date'     => $this->start_date?->toDateString(),
            'end_date'       => $this->end_date?->toDateString(),
            'is_open_ended'  => $this->end_date === null, // true تعني تثبيت مفتوح للأبد حتى يتم تغييره
            'agreed_price'   => (float) $this->agreed_price,
            'status'         => $this->status, // active, paused, cancelled
            'notes'          => $this->notes,
            'created_at'     => $this->created_at?->toDateTimeString(),
            'upcoming_slots_count' => $this->when($this->relationLoaded('slots'), function () {
                return $this->slots->where('date', '>=', now()->toDateString())->count();
            }),
        ];
    }
}
