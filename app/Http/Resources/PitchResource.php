<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PitchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'tenant_id'          => $this->tenant_id,
            'name'               => $this->name,
            'description'        => $this->description,
            'sport_type'         => $this->sport_type,
            'court_size'         => $this->court_size,
            'surface_type'       => $this->surface_type,
            'is_indoor'          => (bool) $this->is_indoor,
            'manual_day_price'   => $this->manual_day_price,
            'manual_night_price' => $this->manual_night_price,
            'online_day_price'   => $this->online_day_price,
            'online_night_price' => $this->online_night_price,
            'night_start_at'     => $this->night_start_at,
            'amenities'          => $this->amenities ?? [],
            'status'             => $this->status,
            'images'             => $this->getMedia('images')->map(fn ($media) => [
                'id'       => $media->id,
                'name'     => $media->file_name,
                'url'      => $media->getUrl(),
                'size'     => $media->size,
                'mime'     => $media->mime_type,
            ]),
            'videos'             => $this->getMedia('videos')->map(fn ($media) => [
                'id'       => $media->id,
                'name'     => $media->file_name,
                'url'      => $media->getUrl(),
                'size'     => $media->size,
                'mime'     => $media->mime_type,
            ]),
            'created_at'         => $this->created_at?->toDateTimeString(),
            'updated_at'         => $this->updated_at?->toDateTimeString(),
        ];
    }
}
