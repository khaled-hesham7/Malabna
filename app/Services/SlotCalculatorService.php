<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotCalculatorService
{
    /**
     * تقطيع الوقت بين بداية ونهاية اليوم لسلوتات زمنية متساوية
     *
     * @param string $date التاريخ المطلوب (YYYY-MM-DD)
     * @param string $openingTime وقت بداية الفتح (HH:MM:SS)
     * @param string $closingTime وقت الإغلاق (HH:MM:SS)
     * @param int $durationMinutes مدة السلوت الواحد بالدقائق
     * @return Collection
     */
    public function generateRawSlots(
        string $date,
        string $openingTime,
        string $closingTime,
        int $durationMinutes = 60
    ): Collection {
        $slots = collect();
        $start = Carbon::parse("{$date} {$openingTime}");
        $end = Carbon::parse("{$date} {$closingTime}");

        // التعامل مع الملاعب التي تقفل بعد منتصف الليل (مثلاً تفتح 6 مساءً وتقفل 2 صباح اليوم التالي)
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        while ($start->copy()->addMinutes($durationMinutes)->lessThanOrEqualTo($end)) {
            $slotStart = $start->format('H:i:s');
            $slotEnd = $start->copy()->addMinutes($durationMinutes)->format('H:i:s');

            $slots->push([
                'start_time' => $slotStart,
                'end_time'   => $slotEnd,
            ]);

            $start->addMinutes($durationMinutes);
        }

        return $slots;
    }

    /**
     * فحص هل السلوت يتقاطع مع أي حجز موجود في قائمة الحجوزات
     *
     * @param string $slotStart وقت بداية السلوت
     * @param string $slotEnd وقت نهاية السلوت
     * @param Collection $occupiedRanges قائمة بالفترات المحجوزة بالفعل
     * @return bool
     */
    public function isSlotOverlapping(string $slotStart, string $slotEnd, Collection $occupiedRanges): bool
    {
        foreach ($occupiedRanges as $range) {
            $rangeStart = is_array($range) ? $range['start_time'] : $range->start_time;
            $rangeEnd = is_array($range) ? $range['end_time'] : $range->end_time;

            // معادلة التقاطع الزمني Standard Overlap Check:
            // السلوت يتقاطع إذا بدأ قبل نهاية الحجز وَ انتهى بعد بداية الحجز
            if ($slotStart < $rangeEnd && $slotEnd > $rangeStart) {
                return true;
            }
        }

        return false;
    }
}
