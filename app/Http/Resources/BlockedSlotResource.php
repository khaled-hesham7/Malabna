<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockedSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'pitch_id'     => $this->pitch_id,
            'blocked_date' => $this->booking_date,
            'start_time'   => $this->start_time,
            'end_time'     => $this->end_time,
            'reason'       => $this->notes,
            'status'       => $this->status,
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
