<?php

namespace App\QueryFilters\Venue;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class PitchIndoorFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $isIndoor = filter_var($value, FILTER_VALIDATE_BOOLEAN);

        $query->whereHas('pitches', function (Builder $q) use ($isIndoor) {
            $q->where('status', 'active')
              ->where('is_indoor', $isIndoor);
        });
    }
}
