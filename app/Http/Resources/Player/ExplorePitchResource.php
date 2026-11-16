<?php

namespace App\Http\Resources\Player;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExplorePitchResource extends JsonResource
{
    /**
     * Transform the resource into an array for the explore list.
     */
    public function toArray(Request $request): array
    {
        // حساب المسافة إذا كانت متوفرة من الاستعلام
        $distance = null;
        if (isset($this->distance)) {
            $distance = round((float) $this->distance, 1);
        }

        // جلب الصور
        $images = $this->getMedia('images');
        $primaryImageUrl = $images->isNotEmpty() ? $images->first()->getUrl() : null;

        // الساعات المتطابقة مع بحث اللاعب (إن وجدت)
        $matchedSlots = [];
        if ($this->relationLoaded('slots')) {
            $matchedSlots = $this->slots->map(function ($slot) {
                return [
                    'id'         => $slot->id,
                    'date'       => $slot->date?->toDateString(),
                    'start_time' => substr($slot->start_time, 0, 5),
                    'end_time'   => substr($slot->end_time, 0, 5),
                    'time_label' => substr($slot->start_time, 0, 5) . ' - ' . substr($slot->end_time, 0, 5),
                    'price'      => (float) $slot->online_price,
                ];
            })->values();
        }

        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'sport_type'         => $this->sport_type,
            'court_size'         => $this->court_size,
            'surface_type'       => $this->surface_type,
            'is_indoor'          => (bool) $this->is_indoor,
            'online_day_price'   => (float) $this->online_day_price,
            'online_night_price' => (float) $this->online_night_price,
            'primary_image_url'  => $primaryImageUrl,
            'distance_km'        => $distance,
            'venue'              => [
                'id'        => $this->tenant?->id ?? $this->tenant_id,
                'name'      => $this->tenant?->name,
                'city'      => $this->tenant?->city,
                'address'   => $this->tenant?->address,
                'logo_url'  => $this->tenant?->getFirstMediaUrl('logo') ?: null,
                'latitude'  => $this->tenant?->latitude ? (float) $this->tenant->latitude : null,
                'longitude' => $this->tenant?->longitude ? (float) $this->tenant->longitude : null,
            ],
            'matched_slots'      => $matchedSlots,
        ];
    }
}

