<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PitchSlot;
use App\Models\RecurringSchedule;
use Carbon\Carbon;

class PitchAvailabilityService
{
    /**
     * التحقق الشامل من توفر الملعب في تاريخ ووقت محددين
     */
    public function isSlotAvailable(int $pitchId, string $date, string $startTime, string $endTime, ?int $ignoreBookingId = null): bool
    {
        // 1. فحص التضارب مع الحجوزات الحالية (Bookings)
        $hasBookingConflict = Booking::where('pitch_id', $pitchId)
            ->where('booking_date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreBookingId, fn($q) => $q->where('id', '!=', $ignoreBookingId))
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($hasBookingConflict) {
            return false;
        }

        // 2. فحص التضارب مع الأوقات المحظورة (Blocked Slots)
        $hasBlockConflict = PitchSlot::where('pitch_id', $pitchId)
            ->where('blocked_date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($hasBlockConflict) {
            return false;
        }

        // 3. فحص التضارب مع الاشتراكات الدورية (Recurring Schedules)
        // بنحدد اليوم من الأسبوع (مثلاً: Thursday)
        $dayOfWeek = Carbon::parse($date)->format('l');

        $hasRecurringConflict = RecurringSchedule::where('pitch_id', $pitchId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($hasRecurringConflict) {
            return false;
        }

        return true;
    }
}
