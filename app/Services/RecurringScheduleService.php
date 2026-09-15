<?php

namespace App\Services;

use App\Models\RecurringSchedule;
use App\Models\Pitch;
use App\Models\Tenant;
use App\Models\PitchSlot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class RecurringScheduleService
{
    protected PitchSlotService $slotService;

    public function __construct(PitchSlotService $slotService)
    {
        $this->slotService = $slotService;
    }

    /**
     * إنشاء حجز دوري أسبوعي ثابت جديد
     */
    public function createRecurringSchedule(
        Tenant $tenant,
        Pitch $pitch,
        string $customerName,
        string $customerPhone,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        float $agreedPrice,
        string $startDate,
        ?string $endDate = null,
        string $bookingType = 'manual'
    ): RecurringSchedule {
        return DB::transaction(function () use ($tenant, $pitch, $customerName, $customerPhone, $dayOfWeek, $startTime, $endTime, $agreedPrice, $startDate, $endDate, $bookingType) {
            $schedule = RecurringSchedule::create([
                'tenant_id'      => $tenant->id,
                'pitch_id'       => $pitch->id,
                'customer_name'  => $customerName,
                'customer_phone' => $customerPhone,
                'day_of_week'    => $dayOfWeek,
                'start_time'     => $startTime,
                'end_time'       => $endTime,
                'agreed_price'   => $agreedPrice,
                'booking_type'   => $bookingType,
                'status'         => 'active',
                'start_date'     => $startDate,
                'end_date'       => $endDate,
            ]);

            // تطبيق الحجز الثابت فوراً على الأسابيع التابعة ضمن النطاق
            $this->applyScheduleToUpcomingSlots($schedule);

            return $schedule;
        });
    }

    /**
     * تطبيق جدول الحجز الدوري الأسبوعي على الفترات القادمة في النطاق المحدد
     */
    public function applyScheduleToUpcomingSlots(RecurringSchedule $schedule, int $weeksAhead = 4): int
    {
        if ($schedule->status !== 'active') {
            return 0;
        }

        $appliedCount = 0;
        $start = Carbon::parse($schedule->start_date);
        $maxEnd = now()->addWeeks($weeksAhead);

        if ($schedule->end_date) {
            $scheduleEnd = Carbon::parse($schedule->end_date);
            if ($scheduleEnd->lt($maxEnd)) {
                $maxEnd = $scheduleEnd;
            }
        }

        // البحث عن أول يوم يطابق day_of_week
        while ($start->dayOfWeek !== $schedule->day_of_week) {
            $start->addDay();
        }

        // الدوران على كل الأيام المطابقة ليوم الأسبوع حتى نهاية النطاق
        while ($start->lte($maxEnd)) {
            $dateString = $start->toDateString();

            // إنشاء أو جلب الفترة الزمنية لهذا اليوم والوقت
            $slot = PitchSlot::firstOrCreate([
                'pitch_id'   => $schedule->pitch_id,
                'date'       => $dateString,
                'start_time' => $schedule->start_time,
            ], [
                'end_time'     => $schedule->end_time,
                'manual_price' => $schedule->agreed_price,
                'online_price' => $schedule->agreed_price,
                'status'       => 'booked',
            ]);

            // ربط الفترة بالحجز الثابت وتحديث حالتها لـ booked
            $slot->update([
                'status'                  => 'booked',
                'is_recurring'            => true,
                'recurring_customer_name' => $schedule->customer_name,
                'recurring_schedule_id'   => $schedule->id,
            ]);

            $appliedCount++;
            $start->addWeek(); // الانتقال للأسبوع القادم مباشرة بنفس اليوم
        }

        return $appliedCount;
    }
}
