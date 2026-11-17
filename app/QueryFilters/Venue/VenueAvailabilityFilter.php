<?php

namespace App\QueryFilters\Venue;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class VenueAvailabilityFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): void
    {
        // القيمة ممكن تكون تاريخ مباشر، أو مصفوفة، أو نقرأ المتغيرات من الـ request
        $date = is_array($value) ? ($value['date'] ?? null) : $value;
        $time = request()->input('filter.time', request()->input('time'));
        $timeFrom = request()->input('filter.time_from', request()->input('time_from'));
        $timeTo = request()->input('filter.time_to', request()->input('time_to'));

        if (blank($date)) {
            if ($time || ($timeFrom && $timeTo)) {
                $date = now()->toDateString();
            } else {
                return;
            }
        }

        $formattedTime = $time ? Carbon::parse($time)->format('H:i:s') : null;
        $formattedFrom = $timeFrom ? Carbon::parse($timeFrom)->format('H:i:s') : null;
        $formattedTo = $timeTo ? Carbon::parse($timeTo)->format('H:i:s') : null;

        $query->whereHas('pitches.slots', function (Builder $slotQ) use ($date, $formattedTime, $formattedFrom, $formattedTo) {
            $slotQ->availableOnline()->where('date', $date);

            if ($formattedTime) {
                $slotQ->where('start_time', $formattedTime);
            } elseif ($formattedFrom && $formattedTo) {
                $slotQ->where('start_time', '>=', $formattedFrom)
                      ->where('start_time', '<=', $formattedTo);
            }
        });
    }
}
