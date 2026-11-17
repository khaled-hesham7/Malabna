<?php

namespace App\QueryFilters\Venue;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class VenueDistanceSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $direction = $descending ? 'desc' : 'asc';
        
        // التحقق من وجود إحداثيات لحساب المسافة
        $lat = request()->input('latitude', request()->input('filter.latitude'));
        $lng = request()->input('longitude', request()->input('filter.longitude'));

        if (!blank($lat) && !blank($lng)) {
            $query->orderBy('distance', $direction);
        } else {
            $query->orderBy('tenants.id', $direction);
        }
    }
}
