<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'pitch_id'       => $this->pitch_id,
            'day_of_week'    => $this->day_of_week,
            'start_time'     => $this->start_time,
            'end_time'       => $this->end_time,
            'customer_name'  => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'start_date'     => $this->start_date,
            'end_date'       => $this->end_date,
            'status'         => $this->status,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
