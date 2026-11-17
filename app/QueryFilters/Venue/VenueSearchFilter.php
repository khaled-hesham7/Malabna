<?php

namespace App\QueryFilters\Venue;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class VenueSearchFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if (blank($value)) {
            return;
        }

        $query->where(function (Builder $q) use ($value) {
            $q->where('tenants.name', 'like', "%{$value}%")
              ->orWhere('tenants.address', 'like', "%{$value}%")
              ->orWhere('tenants.city', 'like', "%{$value}%")
              ->orWhereHas('pitches', function (Builder $pitchQ) use ($value) {
                  $pitchQ->where('status', 'active')
                         ->where('name', 'like', "%{$value}%");
              });
        });
    }
}
