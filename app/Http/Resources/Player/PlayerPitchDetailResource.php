<?php

namespace App\Http\Resources\Player;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerPitchDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array for the pitch detail screen.
     */
    public function toArray(Request $request): array
    {
        $distance = null;
        if (isset($this->distance)) {
            $distance = round((float) $this->distance, 1);
        }

        // معرض الصور المتعددة
        $images = $this->getMedia('images')->map(fn ($m) => [
            'id'  => $m->id,
            'url' => $m->getUrl(),
        ])->values();

        // معرض الفيديوهات المتعددة
        $videos = $this->getMedia('videos')->map(fn ($m) => [
            'id'        => $m->id,
            'url'       => $m->getUrl(),
            'mime_type' => $m->mime_type,
        ])->values();

        return [
            'id'                    => $this->id,
            'name'                  => $this->name,
            'description'           => $this->description,
            'sport_type'            => $this->sport_type,
            'court_size'            => $this->court_size,
            'surface_type'          => $this->surface_type,
            'is_indoor'             => (bool) $this->is_indoor,
            'online_day_price'      => (float) $this->online_day_price,
            'online_night_price'    => (float) $this->online_night_price,
            'night_start_at'        => substr($this->night_start_at ?? '17:00:00', 0, 5),
            'opening_time'          => substr($this->opening_time ?? '08:00:00', 0, 5),
            'closing_time'          => substr($this->closing_time ?? '02:00:00', 0, 5),
            'slot_duration_minutes' => (int) ($this->slot_duration_minutes ?? 60),
            'amenities'             => $this->amenities ?? [],
            'images'                => $images,
            'videos'                => $videos,
            'distance_km'           => $distance,
            'venue'                 => [
                'id'          => $this->tenant?->id,
                'name'        => $this->tenant?->name,
                'city'        => $this->tenant?->city,
                'address'     => $this->tenant?->address,
                'phone'       => $this->tenant?->phone,
                'description' => $this->tenant?->description,
                'logo_url'    => $this->tenant?->getFirstMediaUrl('logo') ?: null,
                'latitude'    => $this->tenant?->latitude ? (float) $this->tenant->latitude : null,
                'longitude'   => $this->tenant?->longitude ? (float) $this->tenant->longitude : null,
            ],
        ];
    }
}

