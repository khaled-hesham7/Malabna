<?php

namespace App\QueryFilters\Venue;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class PitchCourtSizeFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        if (blank($value)) {
            return;
        }

        $query->whereHas('pitches', function (Builder $q) use ($value) {
            $q->where('status', 'active');
            if (is_array($value)) {
                $q->whereIn('court_size', $value);
            } else {
                $q->where('court_size', $value);
            }
        });
    }
}
