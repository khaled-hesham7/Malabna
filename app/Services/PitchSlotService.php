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
    public function generateSlotForDateRange(Pitch $pitch, string $startDate, string $endDate, ?string $openingTime = null, ?string $closingTime = null): int
    {
        $createdCount = 0;

        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        $openTime  = $openingTime ?? ($pitch->opening_time ?? '08:00:00');
        $closeTime = $closingTime ?? ($pitch->closing_time ?? '02:00:00');
        $duration  = (int) ($pitch->slot_duration_minutes ?? 60);

        while ($start->lte($end)) {
            $currentDate = $start->toDateString();

            // مواعيد تشغيل اليوم (من وقت الفتح إلى الإغلاق)
            $timeStart = Carbon::parse("$currentDate $openTime");
            $timeEnd   = Carbon::parse("$currentDate $closeTime");

            // إذا كان وقت الإغلاق يتجاوز منتصف الليل (مثلاً 02:00 فجراً) وليس نهاية اليوم 23:59:59
            if ($timeEnd->lte($timeStart)) {
                $timeEnd->addDay();
            }

            // جلب الحجوزات الثابتة النشطة لهذا الملعب ويوم الأسبوع المطبق
            $currentDayOfWeek = $start->dayOfWeek; // 0 (Sun) to 6 (Sat)
            $recurringSchedules = \App\Models\RecurringSchedule::where('pitch_id', $pitch->id)
                ->where('day_of_week', $currentDayOfWeek)
                ->where('status', 'active')
                ->where('start_date', '<=', $currentDate)
                ->where(function ($q) use ($currentDate) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', $currentDate);
                })
                ->get()
                ->keyBy(function ($item) {
                    return substr($item->start_time, 0, 5);
                });

            // جلب قواعد التسعير المخصصة والمثبتة النشطة لهذا الملعب ويوم الأسبوع
            $pricingRules = \App\Models\PitchPricingRule::where('pitch_id', $pitch->id)
                ->where('status', 'active')
                ->where(function ($q) use ($currentDayOfWeek) {
                    $q->whereNull('day_of_week')
                      ->orWhere('day_of_week', $currentDayOfWeek);
                })
                ->get();

            // اللوب الداخلي يلف على الفترات بالمدة المحددة
            while ($timeStart < $timeEnd) {
                $slotStart = $timeStart->format('H:i:s');
                $slotEndTime = $timeStart->copy()->addMinutes($duration);
                $slotEnd   = $slotEndTime->format('H:i:s');

                // 1. السعر الافتراضي العام (صباحي / مسائي)
                // الفترات بعد منتصف الليل (00:xx, 01:xx) تُعتبر ليلية تلقائياً لأنها قبل وقت الفتح
                $nightStartTime = $pitch->night_start_at ?? '17:00:00';
                $isNight = $slotStart >= $nightStartTime || $slotStart < $openTime;
                $defaultManualPrice = $isNight ? $pitch->manual_night_price : $pitch->manual_day_price;
                $defaultOnlinePrice = $isNight ? $pitch->online_night_price : $pitch->online_day_price;

                // 2. فحص هل توجد قاعدة تسعير مثبتة لهذا اليوم والتوقيت (تعديل دائم لساعة بعينها)
                $matchedRule = $pricingRules->first(function ($rule) use ($slotStart) {
                    return $slotStart >= $rule->start_time && $slotStart < $rule->end_time;
                });

                if ($matchedRule) {
                    $defaultManualPrice = (float) $matchedRule->manual_price_per_hour;
                    $defaultOnlinePrice = (float) $matchedRule->online_price_per_hour;
                }

                // 3. فحص هل هناك حجز ثابت نشط يغطي هذه الساعة لعميل معين (أولوية قصوى)
                $timeKey = substr($slotStart, 0, 5);
                $recurring = $recurringSchedules->get($timeKey);

                $isRecurring = $recurring !== null;
                $initialStatus = $isRecurring ? 'booked' : 'available';
                $finalManualPrice = $isRecurring ? (float) $recurring->agreed_price : ($defaultManualPrice ?? 200.00);
                $finalOnlinePrice = $isRecurring ? (float) $recurring->agreed_price : ($defaultOnlinePrice ?? 180.00);

                // الحفظ بذكاء بدون تكرار في قاعدة البيانات
                $slot = PitchSlot::firstOrCreate([
                    'pitch_id'   => $pitch->id,
                    'date'       => $currentDate,
                    'start_time' => $slotStart,
                ], [
                    'end_time'                => $slotEnd,
                    'manual_price'            => $finalManualPrice,
                    'online_price'            => $finalOnlinePrice,
                    'status'                  => $initialStatus,
                    'is_recurring'            => $isRecurring,
                    'recurring_customer_name' => $isRecurring ? $recurring->customer_name : null,
                    'recurring_schedule_id'   => $isRecurring ? $recurring->id : null,
                ]);

                // لو الفترة كانت متولدة مسبقاً كمتاحة ولكن تم إنشاء حجز ثابت لاحقاً ولم يكن مربوطاً بها بعد
                if (!$slot->wasRecentlyCreated && $isRecurring && $slot->status === 'available' && !$slot->is_recurring) {
                    $slot->update([
                        'status'                  => 'booked',
                        'is_recurring'            => true,
                        'recurring_customer_name' => $recurring->customer_name,
                        'recurring_schedule_id'   => $recurring->id,
                        'manual_price'            => $finalManualPrice,
                        'online_price'            => $finalOnlinePrice,
                    ]);
                }

                if ($slot->wasRecentlyCreated) {
                    $createdCount++;
                }

                // الانتقال للفترة التالية
                $timeStart->addMinutes($duration);
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
