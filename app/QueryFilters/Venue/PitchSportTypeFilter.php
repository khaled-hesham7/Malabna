<?php

namespace App\QueryFilters\Venue;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class PitchSportTypeFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if (blank($value)) {
            return;
        }

        $query->whereHas('pitches', function (Builder $q) use ($value) {
            $q->where('status', 'active');
            if (is_array($value)) {
                $q->whereIn('sport_type', $value);
            } else {
                $q->where('sport_type', $value);
            }
        });
    }
}
