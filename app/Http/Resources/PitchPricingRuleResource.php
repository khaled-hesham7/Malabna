<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PitchPricingRuleResource extends JsonResource
{
    protected static array $dayNames = [
        0 => 'الأحد',
        1 => 'الاثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
        6 => 'السبت',
    ];

    public function toArray(Request $request): array
    {
        $dayOfWeek = $this->day_of_week !== null ? (int) $this->day_of_week : null;

        return [
            'id'                    => $this->id,
            'pitch_id'              => $this->pitch_id,
            'name'                  => $this->name,
            'day_of_week'           => $dayOfWeek,
            'day_name'              => $dayOfWeek !== null ? (self::$dayNames[$dayOfWeek] ?? '') : 'كل الأيام',
            'start_time'            => substr($this->start_time, 0, 5),
            'end_time'              => substr($this->end_time, 0, 5),
            'time_label'            => substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5),
            'manual_price_per_hour' => (float) $this->manual_price_per_hour,
            'online_price_per_hour' => (float) $this->online_price_per_hour,
            'status'                => $this->status,
            'created_at'            => $this->created_at?->toDateTimeString(),
        ];
    }
}
