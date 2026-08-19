<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'start_time'   => $this['start_time'],
            'end_time'     => $this['end_time'],
            'price'        => (float) $this['price'],
            'is_available' => (bool) $this['is_available'],
            'reason'       => $this['reason'] ?? null,
        ];
    }
}
