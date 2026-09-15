<?php

namespace App\Services;

use App\Models\Pitch;
use App\Models\PitchSlot;
use App\Models\User;
use Carbon\Carbon;

class PitchSlotService
{
    /**
     * توليد الفترات الزمنية المجمّعة لملعب محدد خلال نطاق تواريخ معين (افتراضياً 24 ساعة لليوم بالكامل)
     */
    public function generateSlotForDateRange (Pitch $pitch, string $startDate, string $endDate, string $openingTime = '00:00:00', string $closingTime = '23:59:59'): int
    {
        $createdCount = 0;

        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        while ($start->lte($end)) {
            $currentDate = $start->toDateString();

            // مواعيد تشغيل اليوم (من وقت الفتح إلى الإغلاق)
            $timeStart = Carbon::parse("$currentDate $openingTime");
            $timeEnd   = Carbon::parse("$currentDate $closingTime");

            // إذا كان وقت الإغلاق يتجاوز منتصف الليل (مثلاً 02:00 فجراً) وليس نهاية اليوم 23:59:59
            if ($timeEnd->lte($timeStart)) {
                $timeEnd->addDay();
            }

            // اللوب الداخلي يلف على الساعات ساعة بساعة
            while ($timeStart < $timeEnd) {
                $slotStart = $timeStart->format('H:i:s');
                $slotEnd   = $timeStart->copy()->addHour()->format('H:i:s');

                // فحص هل الفترة صباحية أم مسائية وتحديد السعر المناسب
                $isNight = $slotStart >= ($pitch->night_start_at ?? '18:00:00');
                $manualPrice = $isNight ? $pitch->manual_night_price : $pitch->manual_day_price;
                $onlinePrice = $isNight ? $pitch->online_night_price : $pitch->online_day_price;

                // الحفظ بذكاء بدون تكرار في قاعدة البيانات
                $slot = PitchSlot::firstOrCreate([
                    'pitch_id'   => $pitch->id,
                    'date'       => $currentDate,
                    'start_time' => $slotStart,
                ], [
                    'end_time'     => $slotEnd,
                    'manual_price' => $manualPrice ?? 300.00,
                    'online_price' => $onlinePrice ?? 320.00,
                    'status'       => 'available',
                ]);

                if ($slot->wasRecentlyCreated) {
                    $createdCount++;
                }

                // الانتقال للساعة التالية
                $timeStart->addHour();
            }

            // الانتقال لليوم التالي
            $start->addDay();
        }

        return $createdCount;
    }

    /**
     * قفل الفترة الزمنية مؤقتاً لعميل محدد أثناء عملية الدفع أونلاين (منع التضارب Race Condition)
     */
    public function lockSlotForCheckout(PitchSlot $slot, User $user, int $minutes = 5): bool
    {
        // إذا كانت الفترة مقفولة بالفعل لشخص آخر ولم تنتهِ مهلته بعد
        if ($slot->locked_until && $slot->locked_until->isFuture() && $slot->locked_by_user_id !== $user->id) {
            return false;
        }

        // إذا كانت الفترة غير متاحة للحجز (محجوزة أو مقفولة تماماً)
        if (in_array($slot->status, ['booked', 'blocked_all', 'blocked_online'])) {
            return false;
        }

        return $slot->update([
            'locked_by_user_id' => $user->id,
            'locked_until'      => now()->addMinutes($minutes),
        ]);
    }

    /**
     * فك القفل المؤقت عن كل الفترات التي انتهت مهلتها ولم يكتمل دفعها
     */
    public function releaseExpiredLocks(): int
    {
        return PitchSlot::whereNotNull('locked_until')
            ->where('locked_until', '<', now())
            ->update([
                'locked_by_user_id' => null,
                'locked_until'      => null,
            ]);
    }

    /**
     * تحديث حالة فترة محددة (مثل حظر صيانة أو فتح)
     */
    public function updateSlotStatus(PitchSlot $slot, string $newStatus): bool
    {
        $allowedStatuses = ['available', 'booked', 'blocked_all', 'blocked_online', 'blocked_manual'];

        if (!in_array($newStatus, $allowedStatuses)) {
            return false;
        }

        return $slot->update(['status' => $newStatus]);
    }

    public function __construct()
    {
        //
    }
}
