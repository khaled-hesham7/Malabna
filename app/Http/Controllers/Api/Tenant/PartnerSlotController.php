<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\CancelBookingRequest;
use App\Http\Requests\Partner\ManualBookRequest;
use App\Http\Requests\Partner\SlotIndexRequest;
use App\Http\Requests\Partner\ToggleBlockDayRequest;
use App\Http\Requests\Partner\ToggleBlockSlotRequest;
use App\Http\Requests\Partner\UpdateSlotPriceRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PitchSlotResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Pitch;
use App\Models\PitchPricingRule;
use App\Models\PitchSlot;
use App\Services\BookingService;
use App\Services\PitchSlotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartnerSlotController extends Controller
{
    protected PitchSlotService $slotService;
    protected BookingService $bookingService;

    public function __construct(PitchSlotService $slotService, BookingService $bookingService)
    {
        $this->slotService = $slotService;
        $this->bookingService = $bookingService;
    }

    /**
     * عرض جدول الساعات لملعب محدد في تاريخ معين
     * GET /api/v1/partner/pitches/{pitch}/slots?date=YYYY-MM-DD
     */
    public function index(SlotIndexRequest $request, Pitch $pitch): JsonResponse
    {
        $this->authorizeTenantPitch($request, $pitch);

        $selectedDate = $request->query('date', now()->toDateString());
        $carbonDate = Carbon::parse($selectedDate);

        // الحد الأقصى للمستقبل هو 60 يوماً فقط
        $maxAllowedDate = now()->addDays(60)->startOfDay();
        if ($carbonDate->startOfDay()->gt($maxAllowedDate)) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، أقصى فترة مسموح باستعراضها أو حجزها هي 60 يوماً مقدماً.',
            ], 422);
        }

        // إذا لم تكن فترات هذا التاريخ متولدة وتاريخ اليوم ليس في الماضي، نولدها له فوراً
        $existingCount = PitchSlot::where('pitch_id', $pitch->id)
            ->where('date', $selectedDate)
            ->count();

        if ($existingCount === 0 && $pitch->status === 'active' && $carbonDate->gte(now()->startOfDay())) {
            $this->slotService->generateSlotForDateRange($pitch, $selectedDate, $selectedDate);
        }

        $slots = PitchSlot::with(['booking.user', 'recurringSchedule'])
            ->where('pitch_id', $pitch->id)
            ->where('date', $selectedDate)
            ->orderBy('start_time')
            ->get();

        // إحصائيات سريعة لليوم
        $stats = [
            'total_slots'     => $slots->count(),
            'available_slots' => $slots->where('status', 'available')->count(),
            'booked_slots'    => $slots->where('status', 'booked')->count(),
            'recurring_slots' => $slots->where('is_recurring', true)->count(),
            'blocked_slots'   => $slots->whereIn('status', ['blocked_all', 'blocked_online', 'blocked_manual'])->count(),
            'day_revenue'     => $slots->where('status', 'booked')->sum(function ($slot) {
                return $slot->booking?->total_price ?? $slot->manual_price;
            }),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'pitch' => [
                    'id'   => $pitch->id,
                    'name' => $pitch->name,
                ],
                'date'  => $selectedDate,
                'stats' => $stats,
                'slots' => PitchSlotResource::collection($slots),
            ],
        ]);
    }

    /**
     * قفل أو فتح ساعة معينة يدوياً (أونلاين فقط أو بالكامل)
     * PATCH /api/v1/partner/slots/{slot}/toggle-block
     */
    public function toggleBlockSlot(ToggleBlockSlotRequest $request, PitchSlot $slot): JsonResponse
    {
        $this->authorizeTenantSlot($request, $slot);

        // منع التعديل على فترات في الماضي
        if ($slot->date && Carbon::parse($slot->date)->startOfDay()->lt(now()->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل فترات في أيام سابقة.',
            ], 422);
        }

        if ($slot->status === 'booked') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل هذه الفترة لأنها محجوزة بالفعل لعميل.',
            ], 422);
        }

        $blockType = $request->input('block_type');

        // تحديد الحالة الجديدة
        if ($blockType === 'unblock') {
            $newStatus = 'available';
        } elseif ($blockType === 'online') {
            $newStatus = 'blocked_online';
        } elseif ($blockType === 'all') {
            $newStatus = 'blocked_all';
        } else {
            // تبديل تلقائي لو لم يحدد: إذا كانت مقفولة نفتحها، وإذا كانت متاحة نقفلها بالكامل
            $isBlocked = in_array($slot->status, ['blocked_all', 'blocked_online', 'blocked_manual']);
            $newStatus = $isBlocked ? 'available' : 'blocked_all';
        }

        $slot->update([
            'status'            => $newStatus,
            'locked_by_user_id' => null,
            'locked_until'      => null,
        ]);

        $statusMessages = [
            'available'      => 'تم فتح الفترة وأصبحت متاحة للحجز (أونلاين ويدوي).',
            'blocked_online' => 'تم إغلاق الفترة من الحجز الأونلاين فقط (ما زالت متاحة للحجز اليدوي بالملعب).',
            'blocked_all'    => 'تم إغلاق الفترة بالكامل (أونلاين ويدوي).',
        ];

        return response()->json([
            'success' => true,
            'message' => $statusMessages[$newStatus] ?? 'تم تحديث حالة الفترة بنجاح.',
            'data'    => new PitchSlotResource($slot->fresh()),
        ]);
    }

    /**
     * قفل أو فتح يوم كامل للملعب بضغطة زر واحدة (أونلاين فقط أو بالكامل)
     * POST /api/v1/partner/pitches/{pitch}/slots/toggle-day
     */
    public function toggleBlockDay(ToggleBlockDayRequest $request, Pitch $pitch): JsonResponse
    {
        $this->authorizeTenantPitch($request, $pitch);

        $targetDate = $request->date;
        $action     = $request->action;
        $blockType  = $request->input('block_type', 'all');
        $carbonDate = Carbon::parse($targetDate);

        // منع التعامل مع تواريخ في الماضي
        if ($carbonDate->startOfDay()->lt(now()->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن التحكم في أيام سابقة.',
            ], 422);
        }

        // الحد الأقصى للمستقبل هو 60 يوماً فقط
        $maxAllowedDate = now()->addDays(60)->startOfDay();
        if ($carbonDate->startOfDay()->gt($maxAllowedDate)) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، أقصى فترة مسموح بالتحكم فيها هي 60 يوماً مقدماً.',
            ], 422);
        }

        // التأكد من وجود ساعات هذا اليوم أولاً
        $existingCount = PitchSlot::where('pitch_id', $pitch->id)
            ->where('date', $targetDate)
            ->count();

        if ($existingCount === 0 && $pitch->status === 'active') {
            $this->slotService->generateSlotForDateRange($pitch, $targetDate, $targetDate);
        }

        if ($action === 'block') {
            $newStatus = $blockType === 'online' ? 'blocked_online' : 'blocked_all';

            // نغلق كل الفترات غير المحجوزة
            $affected = PitchSlot::where('pitch_id', $pitch->id)
                ->where('date', $targetDate)
                ->where('status', '!=', 'booked')
                ->update([
                    'status'            => $newStatus,
                    'locked_by_user_id' => null,
                    'locked_until'      => null,
                ]);

            $bookedCount = PitchSlot::where('pitch_id', $pitch->id)
                ->where('date', $targetDate)
                ->where('status', 'booked')
                ->count();

            $typeLabel = $blockType === 'online' ? 'من الحجز الأونلاين فقط' : 'بالكامل (أونلاين ويدوي)';
            $message = "تم إغلاق جميع فترات اليوم ({$targetDate}) {$typeLabel} بنجاح.";
            if ($bookedCount > 0) {
                $message .= " تنبيه: يوجد {$bookedCount} فترة محجوزة مسبقاً لم يتم إلغاؤها.";
            }
        } else {
            // فتح كل الفترات المغلقة وجعلها متاحة
            $affected = PitchSlot::where('pitch_id', $pitch->id)
                ->where('date', $targetDate)
                ->whereIn('status', ['blocked_all', 'blocked_online', 'blocked_manual'])
                ->update([
                    'status'            => 'available',
                    'locked_by_user_id' => null,
                    'locked_until'      => null,
                ]);

            $message = "تم فتح جميع فترات اليوم ({$targetDate}) وأصبحت متاحة للحجز مجدداً.";
        }

        return response()->json([
            'success'        => true,
            'message'        => $message,
            'affected_slots' => $affected,
        ]);
    }

    /**
     * تسجيل حجز كاش/يدوي سريع من أرض الملعب
     * POST /api/v1/partner/slots/{slot}/manual-book
     */
    public function manualBook(ManualBookRequest $request, PitchSlot $slot): JsonResponse
    {
        $this->authorizeTenantSlot($request, $slot);

        if ($slot->status === 'booked') {
            return response()->json([
                'success' => false,
                'message' => 'هذه الفترة محجوزة بالفعل.',
            ], 422);
        }

        if (in_array($slot->status, ['blocked_all', 'blocked_manual'])) {
            return response()->json([
                'success' => false,
                'message' => 'عذراً، هذه الفترة مغلقة بالكامل (صيانة/إغلاق). يجب فتحها أولاً لتتمكن من حجزها.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $slot) {
            $user = $request->user();
            $pitch = $slot->pitch;
            $tenant = $pitch->tenant;

            // السعر يؤخذ حصراً ومباشرة من تسعيرة الساعة الرسمية في السيستم لمنع أي تلاعب أو خلل في التقارير
            $totalPrice = (float) $slot->manual_price;

            // فحص العربون
            $hasDeposit = $request->has('deposit_amount') && $request->input('deposit_amount') !== null && $request->input('deposit_amount') !== '';
            if ($hasDeposit && (float) $request->deposit_amount > $totalPrice) {
                return response()->json([
                    'success' => false,
                    'message' => "مبلغ العربون ({$request->deposit_amount} ج.م) لا يمكن أن يتجاوز سعر الحجز الكلي ({$totalPrice} ج.م).",
                ], 422);
            }

            $paidAmount = $hasDeposit ? (float) $request->deposit_amount : $totalPrice;
            $remainingAmount = max(0.00, $totalPrice - $paidAmount);

            // 1. إنشاء الحجز اليدوي
            $booking = Booking::create([
                'booking_code'          => 'MNL-' . strtoupper(Str::random(8)),
                'tenant_id'             => $tenant->id,
                'pitch_id'              => $pitch->id,
                'pitch_slot_id'         => $slot->id,
                'user_id'               => null, // زبون كاش مباشر
                'customer_name'         => $request->customer_name,
                'customer_phone'        => $request->customer_phone,
                'booking_type'          => 'manual',
                'unit_price'            => (float) $slot->manual_price,
                'total_price'           => $totalPrice,
                'deposit_amount'        => $hasDeposit ? (float) $request->deposit_amount : $totalPrice,
                'paid_amount'           => $paidAmount,
                'remaining_amount'      => $remainingAmount,
                'commission_amount'     => 0.00, // الحجز اليدوي عمولته 0 للمنصة
                'status'                => 'confirmed',
            ]);

            // 2. تحديث حالة الفترة لـ booked
            $slot->update([
                'status'            => 'booked',
                'locked_by_user_id' => null,
                'locked_until'      => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الحجز اليدوي بنجاح وحجز الفترة.',
                'data'    => [
                    'booking' => new BookingResource($booking),
                    'slot'    => new PitchSlotResource($slot->fresh(['booking'])),
                ],
            ], 201);
        });
    }

    /**
     * تعديل سعر ساعة بعينها (مع إمكانية تثبيت السعر لهذه الساعة في كل الأسابيع القادمة)
     * PATCH /api/v1/partner/slots/{slot}/update-price
     */
    public function updatePrice(UpdateSlotPriceRequest $request, PitchSlot $slot): JsonResponse
    {
        $this->authorizeTenantSlot($request, $slot);

        $manualPrice = (float) $request->manual_price;
        $onlinePrice = $request->filled('online_price') ? (float) $request->online_price : $manualPrice;
        $applyToFuture = $request->boolean('apply_to_future');

        return DB::transaction(function () use ($slot, $manualPrice, $onlinePrice, $applyToFuture) {
            // 1. تحديث سعر هذه الساعة تحديداً
            $slot->update([
                'manual_price' => $manualPrice,
                'online_price' => $onlinePrice,
            ]);

            if ($applyToFuture) {
                $dayOfWeek = Carbon::parse($slot->date)->dayOfWeek;

                // 2. تحديث كل الساعات المستقبلية لنفس الملعب ويوم الأسبوع ونفس التوقيت (غير المحجوزة)
                PitchSlot::where('pitch_id', $slot->pitch_id)
                    ->where('date', '>=', $slot->date)
                    ->where('start_time', $slot->start_time)
                    ->where('status', '!=', 'booked')
                    ->where('is_recurring', false)
                    ->update([
                        'manual_price' => $manualPrice,
                        'online_price' => $onlinePrice,
                    ]);

                // 3. إنشاء أو تحديث قاعدة تسعير مخصصة (PitchPricingRule) لتطبق تلقائياً على كل الساعات المستقبلية للأبد
                PitchPricingRule::updateOrCreate([
                    'pitch_id'    => $slot->pitch_id,
                    'day_of_week' => $dayOfWeek,
                    'start_time'  => $slot->start_time,
                    'end_time'    => $slot->end_time,
                ], [
                    'name'                  => "تثبيت سعر ساعة " . substr($slot->start_time, 0, 5),
                    'manual_price_per_hour' => $manualPrice,
                    'online_price_per_hour' => $onlinePrice,
                    'status'                => 'active',
                ]);

                // 4. تحديث سعر أي حجز دوري ثابت نشط على نفس الموعد ليواكب السعر الجديد
                $activeRecurring = \App\Models\RecurringSchedule::where('pitch_id', $slot->pitch_id)
                    ->where('day_of_week', $dayOfWeek)
                    ->where('start_time', $slot->start_time)
                    ->where('status', 'active')
                    ->first();

                if ($activeRecurring) {
                    $activeRecurring->update(['agreed_price' => $manualPrice]);
                    PitchSlot::where('recurring_schedule_id', $activeRecurring->id)
                        ->where('date', '>=', $slot->date)
                        ->update([
                            'manual_price' => $manualPrice,
                            'online_price' => $onlinePrice,
                        ]);
                }

                $message = 'تم تحديث سعر هذه الساعة وتثبيته لنفس الموعد في كل الأسابيع القادمة بنجاح.';
            } else {
                $message = 'تم تحديث سعر هذه الساعة لهذا اليوم بنجاح.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => new PitchSlotResource($slot->fresh(['booking'])),
            ]);
        });
    }

    /**
     * إلغاء حجز قائم لفترة زمنية محددة وإعادة فتح الفترة للحجز مجدداً
     * POST /api/v1/partner/slots/{slot}/cancel-booking
     */
    public function cancelBooking(CancelBookingRequest $request, PitchSlot $slot): JsonResponse
    {
        $this->authorizeTenantSlot($request, $slot);

        $booking = $slot->booking;
        if (!$booking || $booking->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد حجز مؤكد ونشط لهذه الفترة لإلغائه.',
            ], 422);
        }

        // منع إلغاء حجوزات في أيام سابقة
        if ($slot->date && Carbon::parse($slot->date)->startOfDay()->lt(now()->startOfDay())) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء حجوزات لفترات في أيام سابقة.',
            ], 422);
        }

        $reason = $request->input('reason', 'اعتذار العميل عن الماتش');
        $refundDeposit = $request->boolean('refund_deposit', false);

        $this->bookingService->cancelBooking($booking, $reason, $request->user(), $refundDeposit);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الحجز بنجاح وأصبحت الفترة متاحة للحجز مجدداً.',
            'data'    => [
                'booking' => new BookingResource($booking->fresh()),
                'slot'    => new PitchSlotResource($slot->fresh(['booking'])),
            ],
        ]);
    }

    /**
     * التحقق من أن الملعب ينتمي لنادي المستخدم الحالي
     */
    protected function authorizeTenantPitch(Request $request, Pitch $pitch): void
    {
        $tenantId = $request->user()->tenant_id;

        if (!$tenantId || (int) $pitch->tenant_id !== (int) $tenantId) {
            abort(403, 'غير مصرح لك بالوصول لهذا الملعب.');
        }
    }

    /**
     * التحقق من أن الفترة الزمنية تنتمي لنادي المستخدم الحالي
     */
    protected function authorizeTenantSlot(Request $request, PitchSlot $slot): void
    {
        $slot->loadMissing('pitch');
        $this->authorizeTenantPitch($request, $slot->pitch);
    }
}
