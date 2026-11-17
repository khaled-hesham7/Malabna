<?php

namespace App\QueryFilters\Venue;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class VenueMaxDistanceFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if (blank($value) || !is_numeric($value)) {
            return;
        }

        $lat = request()->input('latitude', request()->input('filter.latitude'));
        $lng = request()->input('longitude', request()->input('filter.longitude'));

        if (!blank($lat) && !blank($lng)) {
            $query->havingRaw('distance <= ?', [(float) $value]);
        }
    }
}
