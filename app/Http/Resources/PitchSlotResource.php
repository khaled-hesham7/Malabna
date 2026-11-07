<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PitchSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'pitch_id'     => $this->pitch_id,
            'date'         => $this->date?->toDateString(),
            'start_time'   => substr($this->start_time, 0, 5), // عرض HH:mm مثلاً 18:00
            'end_time'     => substr($this->end_time, 0, 5),   // عرض HH:mm مثلاً 19:00
            'time_label'   => substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5),
            'manual_price' => $this->manual_price,
            'online_price' => $this->online_price,
            'status'       => $this->status, // available, booked, locked, blocked
            'is_locked'    => (bool) $this->isLocked(),
            'is_recurring' => (bool) $this->is_recurring,
            'recurring'    => $this->when($this->is_recurring, function () {
                return [
                    'schedule_id'   => $this->recurring_schedule_id,
                    'customer_name' => $this->recurring_customer_name ?? $this->recurringSchedule?->customer_name,
                    'customer_phone'=> $this->recurringSchedule?->customer_phone,
                    'agreed_price'  => $this->recurringSchedule?->agreed_price ? (float) $this->recurringSchedule->agreed_price : null,
                ];
            }),
            'booking'      => $this->when($this->relationLoaded('booking') && $this->booking && $this->status === 'booked', function () {
                return [
                    'id'             => $this->booking->id,
                    'booking_code'   => $this->booking->booking_code,
                    'customer_name'  => $this->booking->customer_name ?? $this->booking->user?->name,
                    'customer_phone' => $this->booking->customer_phone ?? $this->booking->user?->phone,
                    'booking_type'   => $this->booking->booking_type,
                    'total_price'      => $this->booking->total_price,
                    'paid_amount'      => $this->booking->paid_amount,
                    'remaining_amount' => $this->booking->remaining_amount,
                    'status'           => $this->booking->status,
                ];
            }),
        ];
    }
}
