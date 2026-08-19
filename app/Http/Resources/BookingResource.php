<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'pitch_id'       => $this->pitch_id,
            'booking_date'   => $this->booking_date,
            'start_time'     => $this->start_time,
            'end_time'       => $this->end_time,
            'customer_name'  => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'total_price'    => (float) $this->total_price,
            'payment_method' => $this->payment_method,
            'status'         => $this->status,
            'notes'          => $this->notes,
            'created_at'     => $this->created_at?->toIso8601String(),
        ];
    }
}
