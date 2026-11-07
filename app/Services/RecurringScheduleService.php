<?php

namespace App\Services;

use App\Models\Pitch;
use App\Models\PitchSlot;
use App\Models\RecurringSchedule;
use App\Models\Tenant;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class RecurringScheduleService
{
    protected PitchSlotService $slotService;

    public function __construct(PitchSlotService $slotService)
    {
        $this->slotService = $slotService;
    }

    /**
     * إنشاء حجز دوري أسبوعي ثابت جديد (مفتوح للأبد أو بمدة محددة)
     */
    public function createRecurringSchedule(Tenant $tenant, Pitch $pitch, array $data): RecurringSchedule
    {
        return DB::transaction(function () use ($tenant, $pitch, $data) {
            $dayOfWeek = (int) $data['day_of_week'];
            $startTime = Carbon::parse($data['start_time'])->format('H:i:s');
            
            // حساب وقت النهاية تلقائياً إن لم يُمرر بالاعتماد على مدة فترة الملعب
            if (!empty($data['end_time'])) {
                $endTime = Carbon::parse($data['end_time'])->format('H:i:s');
            } else {
                $duration = (int) ($pitch->slot_duration_minutes ?? 60);
                $endTime = Carbon::parse($startTime)->addMinutes($duration)->format('H:i:s');
            }

            // التحقق من عدم وجود حجز ثابت نشط مسبقاً في نفس اليوم ونفس التوقيت لهذا الملعب
            $existingConflict = RecurringSchedule::where('pitch_id', $pitch->id)
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', $startTime)
                ->where('status', 'active')
                ->exists();

            if ($existingConflict) {
                throw new Exception('يوجد بالفعل حجز ثابت نشط لهذا الملعب في نفس يوم الأسبوع ونفس التوقيت.');
            }

            $startDate = !empty($data['start_date']) 
                ? Carbon::parse($data['start_date'])->toDateString() 
                : now()->toDateString();

            $endDate = !empty($data['end_date']) 
                ? Carbon::parse($data['end_date'])->toDateString() 
                : null; // null يعني تثبيت مفتوح للأبد حتى يتم تغييره أو إلغاؤه

            // السعر المعتمد يؤخذ تلقائياً وحصراً من تسعيرة الساعة الرسمية في السيستم
            $agreedPrice = $this->resolveOfficialSlotPrice($pitch, $dayOfWeek, $startTime);

            $schedule = RecurringSchedule::create([
                'tenant_id'      => $tenant->id,
                'pitch_id'       => $pitch->id,
                'user_id'        => $data['user_id'] ?? null,
                'customer_name'  => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'booking_type'   => $data['booking_type'] ?? 'manual',
                'day_of_week'    => $dayOfWeek,
                'start_time'     => $startTime,
                'end_time'       => $endTime,
                'start_date'     => $startDate,
                'end_date'       => $endDate,
                'agreed_price'   => $agreedPrice,
                'status'         => 'active',
                'notes'          => $data['notes'] ?? null,
            ]);

            // تطبيق الحجز فوراً على كل الساعات المستقبلية المتاحة (لـ 8 أسابيع / 60 يوماً)
            $this->applyScheduleToUpcomingSlots($schedule, 8);

            return $schedule;
        });
    }

    /**
     * تطبيق وتسكين الحجز الثابت على الفترات القادمة في النطاق المحدد
     */
    public function applyScheduleToUpcomingSlots(RecurringSchedule $schedule, int $weeksAhead = 8): int
    {
        if ($schedule->status !== 'active') {
            return 0;
        }

        $appliedCount = 0;
        
        // نبدأ من تاريخ اليوم أو من تاريخ بداية الحجز أيهما أحدث (لمنع التعديل على الماضي)
        $scheduleStart = Carbon::parse($schedule->start_date)->startOfDay();
        $today = now()->startOfDay();
        $cursor = $scheduleStart->gt($today) ? $scheduleStart->copy() : $today->copy();

        $maxEnd = now()->addWeeks($weeksAhead)->endOfDay();
        if ($schedule->end_date) {
            $scheduleEnd = Carbon::parse($schedule->end_date)->endOfDay();
            if ($scheduleEnd->lt($maxEnd)) {
                $maxEnd = $scheduleEnd;
            }
        }

        // الانتقال لأول يوم قادم يطابق day_of_week
        while ($cursor->dayOfWeek !== $schedule->day_of_week) {
            $cursor->addDay();
        }

        // التكرار أسبوعياً حتى نهاية النطاق
        while ($cursor->lte($maxEnd)) {
            $dateString = $cursor->toDateString();

            // فحص هل الفترة موجودة، وإلا نولدها
            $slot = PitchSlot::where('pitch_id', $schedule->pitch_id)
                ->where('date', $dateString)
                ->where('start_time', $schedule->start_time)
                ->first();

            if (!$slot) {
                $slot = PitchSlot::create([
                    'pitch_id'                => $schedule->pitch_id,
                    'date'                    => $dateString,
                    'start_time'              => $schedule->start_time,
                    'end_time'                => $schedule->end_time,
                    'manual_price'            => $schedule->agreed_price,
                    'online_price'            => $schedule->agreed_price,
                    'status'                  => 'booked',
                    'is_recurring'            => true,
                    'recurring_customer_name' => $schedule->customer_name,
                    'recurring_schedule_id'   => $schedule->id,
                ]);
                $appliedCount++;
            } else {
                // إذا كانت الفترة موجودة وليست محجوزة بحجز فردي فعلي لعميل آخر
                $hasIndividualBooking = $slot->status === 'booked' 
                    && $slot->booking()->exists() 
                    && $slot->recurring_schedule_id !== $schedule->id;

                if (!$hasIndividualBooking) {
                    $slot->update([
                        'status'                  => 'booked',
                        'is_recurring'            => true,
                        'recurring_customer_name' => $schedule->customer_name,
                        'recurring_schedule_id'   => $schedule->id,
                        'manual_price'            => $schedule->agreed_price,
                        'online_price'            => $schedule->agreed_price,
                    ]);
                    $appliedCount++;
                }
            }

            $cursor->addWeek();
        }

        return $appliedCount;
    }

    /**
     * إلغاء الحجز الثابت بالكامل وتحرير الفترات المستقبلية لتصبح متاحة مجدداً
     */
    public function cancelRecurringSchedule(RecurringSchedule $schedule): void
    {
        DB::transaction(function () use ($schedule) {
            $schedule->update(['status' => 'cancelled']);

            // تحرير جميع الفترات المستقبلية المربوطة بهذا الحجز الثابت
            PitchSlot::where('recurring_schedule_id', $schedule->id)
                ->where('date', '>=', now()->toDateString())
                ->whereDoesntHave('booking') // لا نلمس الفترات التي تم دفعها بحجز فردي فعلي
                ->update([
                    'status'                  => 'available',
                    'is_recurring'            => false,
                    'recurring_customer_name' => null,
                    'recurring_schedule_id'   => null,
                ]);
        });
    }

    /**
     * إيقاف مؤقت أو استئناف الحجز الثابت (Toggle Pause/Resume)
     */
    public function togglePauseRecurringSchedule(RecurringSchedule $schedule): string
    {
        return DB::transaction(function () use ($schedule) {
            if ($schedule->status === 'active') {
                $schedule->update(['status' => 'paused']);

                // عند الإيقاف المؤقت، نحرر الفترات المستقبلية لتصبح متاحة
                PitchSlot::where('recurring_schedule_id', $schedule->id)
                    ->where('date', '>=', now()->toDateString())
                    ->whereDoesntHave('booking')
                    ->update([
                        'status'                  => 'available',
                        'is_recurring'            => false,
                        'recurring_customer_name' => null,
                        'recurring_schedule_id'   => null,
                    ]);

                return 'paused';
            } else {
                // عند الاستئناف، نفعّله ونعيد تسكينه على الفترات القادمة فوراً
                $schedule->update(['status' => 'active']);
                $this->applyScheduleToUpcomingSlots($schedule, 8);

                return 'active';
            }
        });
    }

    /**
     * استخراج السعر الرسمي المعتمد في السيستم لهذه الساعة واليوم
     * (يفحص جدول الساعات، ثم قواعد التسعير المثبتة، ثم تسعيرة الملعب الافتراضية)
     */
    public function resolveOfficialSlotPrice(Pitch $pitch, int $dayOfWeek, string $startTime): float
    {
        // 1. فحص ما إذا كانت هناك فترة متولدة مسبقاً في أقرب يوم أسبوع وتوقيت مطابق
        $nextDate = now()->startOfDay();
        while ($nextDate->dayOfWeek !== $dayOfWeek) {
            $nextDate->addDay();
        }

        $existingSlot = PitchSlot::where('pitch_id', $pitch->id)
            ->where('date', $nextDate->toDateString())
            ->where('start_time', $startTime)
            ->first();

        if ($existingSlot && (float) $existingSlot->manual_price > 0) {
            return (float) $existingSlot->manual_price;
        }

        // 2. فحص قواعد التسعير المخصصة والمثبتة مسبقاً لهذا الملعب ويوم الأسبوع
        $matchedRule = \App\Models\PitchPricingRule::where('pitch_id', $pitch->id)
            ->where('status', 'active')
            ->where(function ($q) use ($dayOfWeek) {
                $q->whereNull('day_of_week')
                  ->orWhere('day_of_week', $dayOfWeek);
            })
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>', $startTime)
            ->first();

        if ($matchedRule && (float) $matchedRule->manual_price_per_hour > 0) {
            return (float) $matchedRule->manual_price_per_hour;
        }

        // 3. السعر الافتراضي للملعب (صباحي أو مسائي)
        $openTime = $pitch->opening_time ?? '08:00:00';
        $nightStartTime = $pitch->night_start_at ?? '17:00:00';
        $isNight = $startTime >= $nightStartTime || $startTime < $openTime;

        return (float) ($isNight ? $pitch->manual_night_price : $pitch->manual_day_price);
    }

    /**
     * تعديل بيانات الحجز الثابت وتحديث الفترات المستقبلية التابعة له
     */
    public function updateRecurringSchedule(RecurringSchedule $schedule, array $data): RecurringSchedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            $schedule->update(array_filter([
                'customer_name'  => $data['customer_name'] ?? $schedule->customer_name,
                'customer_phone' => $data['customer_phone'] ?? $schedule->customer_phone,
                'end_date'       => array_key_exists('end_date', $data) ? $data['end_date'] : $schedule->end_date,
                'notes'          => array_key_exists('notes', $data) ? $data['notes'] : $schedule->notes,
                'status'         => $data['status'] ?? $schedule->status,
            ], fn ($val) => $val !== null));

            // تحديث اسم العميل على الفترات المستقبلية المربوطة بهذا الحجز
            PitchSlot::where('recurring_schedule_id', $schedule->id)
                ->where('date', '>=', now()->toDateString())
                ->update([
                    'recurring_customer_name' => $schedule->customer_name,
                ]);

            return $schedule->fresh();
        });
    }

    /**
     * إلغاء/اعتذار عن أسبوع واحد فقط لحجز ثابت بواسطة التاريخ
     */
    public function releaseRecurringByDate(RecurringSchedule $schedule, string $dateString): PitchSlot
    {
        $carbonDate = Carbon::parse($dateString);

        if ($carbonDate->startOfDay()->lt(now()->startOfDay())) {
            throw new Exception('لا يمكن فك أو تعديل فترات في أيام سابقة.');
        }

        if ($carbonDate->dayOfWeek !== $schedule->day_of_week) {
            throw new Exception('التاريخ المحدد لا يطابق يوم الأسبوع الخاص بهذا الحجز الثابت.');
        }

        // البحث عن الفترة المتولدة لهذا اليوم والتوقيت
        $slot = PitchSlot::where('pitch_id', $schedule->pitch_id)
            ->where('date', $dateString)
            ->where('start_time', $schedule->start_time)
            ->first();

        if (!$slot) {
            // إذا لم تكن الفترة متولدة بعد، ننشئها فوراً كفترة متاحة للعامة
            $duration = (int) ($schedule->pitch?->slot_duration_minutes ?? 60);
            $endTime = $schedule->end_time ?? Carbon::parse($schedule->start_time)->addMinutes($duration)->format('H:i:s');
            $price = $this->resolveOfficialSlotPrice($schedule->pitch, $schedule->day_of_week, $schedule->start_time);

            return PitchSlot::create([
                'pitch_id'                => $schedule->pitch_id,
                'date'                    => $dateString,
                'start_time'              => $schedule->start_time,
                'end_time'                => $endTime,
                'manual_price'            => $price,
                'online_price'            => $price,
                'status'                  => 'available',
                'is_recurring'            => false,
                'recurring_customer_name' => null,
                'recurring_schedule_id'   => null,
            ]);
        }

        $this->releaseSingleSlot($slot);

        return $slot->fresh();
    }

    /**
     * إلغاء/اعتذار عن أسبوع واحد فقط لحجز ثابت (تصبح الساعة متاحة لهذا الأسبوع فقط)
     * مع بقاء الحجز الثابت فعالاً ومستمراً في باقي الأسابيع القادمة
     */
    public function releaseSingleSlot(PitchSlot $slot): void
    {
        if (!$slot->is_recurring && !$slot->recurring_schedule_id) {
            throw new Exception('هذه الفترة ليست حجزاً ثابتاً.');
        }

        $slot->update([
            'status'                  => 'available',
            'is_recurring'            => false,
            'recurring_customer_name' => null,
            'recurring_schedule_id'   => null,
            'locked_by_user_id'       => null,
            'locked_until'            => null,
        ]);
    }
}
