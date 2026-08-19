<?php

namespace App\Actions\Booking;

use App\Models\Pitch;
use App\Models\Booking;
use App\Models\RecurringSchedule;
use App\Services\SlotCalculatorService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GetAvailableSlotsAction
{
    public function __construct(
        protected SlotCalculatorService $slotCalculator
    ) {}

    /**
     * تنفيذ الـ Action وجلب كل السلوتات المتاحة والمحجوزة لليوم المحدد
     *
     * @param Pitch $pitch موديل الملعب المطلوب
     * @param string $date التاريخ بفرمتة YYYY-MM-DD
     * @return Collection
     */
    public function execute(Pitch $pitch, string $date): Collection
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeek; // 0 (Sunday) to 6 (Saturday)

        // 1. جلب ساعات عمل الملعب لهذا اليوم من قاعدة البيانات
        $workingHours = $pitch->schedules()->where('day_of_week', $dayOfWeek)->first();

        if (!$workingHours || !$workingHours->is_open) {
            return collect(); // الملعب مغلق تماماً في هذا اليوم
        }

        // 2. تقطيع الوقت لسلوتات زمنية باستخدام الـ Service
        $rawSlots = $this->slotCalculator->generateRawSlots(
            $date,
            $workingHours->opening_time,
            $workingHours->closing_time,
            $pitch->slot_duration_minutes ?? 60
        );

        // 3. جلب الحجوزات الفعلية والتثبيتية لليوم المحدد من الـ Database
        $existingBookings = $this->getExistingBookings($pitch->id, $date);
        $recurringSchedules = $this->getRecurringSchedules($pitch->id, $dayOfWeek, $date);

        // 4. مطابقة كل سلوت ومعرفة هل متاح وحساب السعر الديناميكي
        return $rawSlots->map(function ($slot) use ($pitch, $date, $existingBookings, $recurringSchedules) {
            $isBooked = $this->slotCalculator->isSlotOverlapping($slot['start_time'], $slot['end_time'], $existingBookings);
            $isRecurring = $this->slotCalculator->isSlotOverlapping($slot['start_time'], $slot['end_time'], $recurringSchedules);

            $isAvailable = !$isBooked && !$isRecurring;

            return [
                'start_time'   => $slot['start_time'],
                'end_time'     => $slot['end_time'],
                'price'        => $this->resolveSlotPrice($pitch, $slot['start_time']),
                'is_available' => $isAvailable,
                'reason'       => !$isAvailable ? ($isBooked ? 'booked' : 'recurring') : null,
            ];
        });
    }

    /**
     * جلب الحجوزات القائمة فقط في اليوم المحدد
     */
    private function getExistingBookings(int $pitchId, string $date): Collection
    {
        return Booking::where('pitch_id', $pitchId)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['confirmed', 'pending', 'blocked'])
            ->get(['start_time', 'end_time']);
    }

    /**
     * جلب التثبيتات الأسبوعية النشطة لنفس يوم الأسبوع
     */
    private function getRecurringSchedules(int $pitchId, int $dayOfWeek, string $date): Collection
    {
        return RecurringSchedule::where('pitch_id', $pitchId)
            ->where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->get(['start_time', 'end_time']);
    }

    /**
     * حساب سعر السلوت (التحقق من قواعد التسعير الديناميكي للذروة)
     */
    private function resolveSlotPrice(Pitch $pitch, string $startTime): float
{
    $pricingRule = $pitch->pricingRules()
        ->where('status', 'active') // 👈 تم التعديل هنا
        ->whereTime('start_time', '<=', $startTime)
        ->whereTime('end_time', '>', $startTime)
        ->first();

    return $pricingRule ? (float) $pricingRule->price : (float) $pitch->base_price_per_hour;
}
}
