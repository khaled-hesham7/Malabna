<?php

namespace App\QueryFilters\Venue;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class PitchMaxPriceFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if (blank($value) || !is_numeric($value)) {
            return;
        }

        $maxPrice = (float) $value;

        $query->whereHas('pitches', function (Builder $q) use ($maxPrice) {
            $q->where('status', 'active')
              ->where(function (Builder $sub) use ($maxPrice) {
                  $sub->where('online_day_price', '<=', $maxPrice)
                      ->orWhere('online_night_price', '<=', $maxPrice);
              });
        });
    }
}
