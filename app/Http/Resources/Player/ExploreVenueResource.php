<?php

namespace App\Http\Resources\Player;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExploreVenueResource extends JsonResource
{
    /**
     * تحويل بيانات النادي والملاعب التابعة له لقائمة الاستكشاف السريعة
     */
    public function toArray(Request $request): array
    {
        $distance = null;
        if (isset($this->distance)) {
            $distance = round((float) $this->distance, 1);
        }

        // الملاعب النشطة التابعة للنادي
        $pitchesCollection = $this->relationLoaded('pitches') ? $this->pitches : collect();

        // الرياضات المتاحة في هذا النادي
        $sportsAvailable = $pitchesCollection->pluck('sport_type')->filter()->unique()->values();

        // حساب أقل سعر يبدأ به الحجز في النادي
        $minDay = $pitchesCollection->min('online_day_price');
        $minNight = $pitchesCollection->min('online_night_price');
        $startingPrice = null;
        if ($minDay !== null && $minNight !== null) {
            $startingPrice = (float) min($minDay, $minNight);
        } elseif ($minDay !== null) {
            $startingPrice = (float) $minDay;
        } elseif ($minNight !== null) {
            $startingPrice = (float) $minNight;
        }

        // تفاصيل كروت الملاعب التابعة للنادي (بدون حشو الساعات)
        $pitchesList = $pitchesCollection->map(function ($pitch) {
            $images = $pitch->getMedia('images');
            $primaryImage = $images->isNotEmpty() ? $images->first()->getUrl() : null;

            $minPitchPrice = min((float) $pitch->online_day_price, (float) $pitch->online_night_price);

            return [
                'id'                 => $pitch->id,
                'name'               => $pitch->name,
                'sport_type'         => $pitch->sport_type,
                'court_size'         => $pitch->court_size,
                'surface_type'       => $pitch->surface_type,
                'is_indoor'          => (bool) $pitch->is_indoor,
                'online_day_price'   => (float) $pitch->online_day_price,
                'online_night_price' => (float) $pitch->online_night_price,
                'starting_price'     => $minPitchPrice,
                'primary_image_url'  => $primaryImage,
            ];
        })->values();

        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'slug'             => $this->slug,
            'city'             => $this->city,
            'address'          => $this->address,
            'phone'            => $this->phone,
            'logo_url'         => $this->getFirstMediaUrl('logo') ?: null,
            'distance_km'      => $distance,
            'sports_available' => $sportsAvailable,
            'starting_price'   => $startingPrice,
            'pitches_count'    => $pitchesCollection->count(),
            'pitches'          => $pitchesList,
        ];
    }
}

