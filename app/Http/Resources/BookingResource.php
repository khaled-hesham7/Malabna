<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'booking_code'          => $this->booking_code,
            'tenant_id'             => $this->tenant_id,
            'pitch_id'              => $this->pitch_id,
            'pitch_slot_id'         => $this->pitch_slot_id,
            'user_id'               => $this->user_id,
            'customer_name'         => $this->customer_name ?? $this->user?->name,
            'customer_phone'        => $this->customer_phone ?? $this->user?->phone,
            'booking_type'          => $this->booking_type,
            'unit_price'            => (float) $this->unit_price,
            'total_price'           => (float) $this->total_price,
            'deposit_amount'        => (float) $this->deposit_amount,
            'paid_amount'           => (float) $this->paid_amount,
            'remaining_amount'      => (float) $this->remaining_amount,
            'commission_amount'     => (float) $this->commission_amount,
            'status'                => $this->status,
            'cancellation_reason'   => $this->cancellation_reason,
            'cancelled_at'          => $this->cancelled_at?->toDateTimeString(),
            'created_at'            => $this->created_at?->toDateTimeString(),
            'pitch'                 => new PitchResource($this->whenLoaded('pitch')),
            'slot'                  => new PitchSlotResource($this->whenLoaded('slot')),
            'user'                  => new UserResource($this->whenLoaded('user')),
            'tenant'                => new TenantResource($this->whenLoaded('tenant')),
        ];
    }
}
