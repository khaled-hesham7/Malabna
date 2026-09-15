<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PitchSlot;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class BookingService
{
    protected PitchSlotService $slotService;

    public function __construct(PitchSlotService $slotService)
    {
        $this->slotService = $slotService;
    }

    /**
     * إنشاء حجز أونلاين عبر التطبيق/الموقع بواسطة العميل
     */
    public function createOnlineBooking(PitchSlot $slot, User $customer, string $paymentMethod = 'online_gateway'): Booking
    {
        // 1. فحص وتوثيق قفل الفترة مؤقتاً
        if (!$this->slotService->lockSlotForCheckout($slot, $customer)) {
            throw new Exception('عذراً، هذه الفترة غير متاحة أو قيد الحجز بواسطة شخص آخر حالياً.');
        }

        return DB::transaction(function () use ($slot, $customer, $paymentMethod) {
            $pitch = $slot->pitch;
            $tenant = $pitch->tenant;

            $unitPrice = $slot->online_price;
            $totalPrice = $unitPrice;

            // حساب العربون المطلوب (مثلاً 50 ج.م أو من قواعد التسعير)
            $depositAmount = min(50.00, $totalPrice);
            $remainingAmount = $totalPrice - $depositAmount;

            // حساب عمولة المنصة على الحجز الأونلاين بناءً على إعدادات النادي
            $commissionAmount = 0.00;
            if ($tenant->commission_type === 'percentage') {
                $commissionAmount = ($totalPrice * $tenant->commission_rate) / 100;
            } elseif ($tenant->commission_type === 'fixed') {
                $commissionAmount = $tenant->commission_rate;
            }

            // 2. إنشاء كود الحجز المرجعي الفريد
            $bookingCode = 'MLB-' . strtoupper(Str::random(6));

            // 3. إنشاء سجل الحجز
            $booking = Booking::create([
                'booking_code'      => $bookingCode,
                'tenant_id'         => $tenant->id,
                'pitch_id'          => $pitch->id,
                'pitch_slot_id'     => $slot->id,
                'user_id'           => $customer->id,
                'booking_type'      => 'online',
                'unit_price'        => $unitPrice,
                'total_price'       => $totalPrice,
                'deposit_amount'    => $depositAmount,
                'paid_amount'       => 0.00,
                'remaining_amount'  => $remainingAmount,
                'commission_amount' => $commissionAmount,
                'status'            => 'pending_payment',
            ]);

            // 4. تحديث حالة الفترة لـ booked
            $this->slotService->updateSlotStatus($slot, 'booked');

            return $booking;
        });
    }

    /**
     * إنشاء حجز يدوي مباشر في الملعب بواسطة الموظف
     */
    public function createManualBooking(
        PitchSlot $slot,
        string $customerName,
        string $customerPhone,
        float $paidAmount,
        ?User $staffUser = null,
        string $paymentMethod = 'cash'
    ): Booking {
        if (in_array($slot->status, ['booked', 'blocked_all', 'blocked_online'])) {
            throw new Exception('عذراً، هذه الفترة غير متاحة للحجز المباشر بالملعب.');
        }

        return DB::transaction(function () use ($slot, $customerName, $customerPhone, $paidAmount, $staffUser, $paymentMethod) {
            $pitch = $slot->pitch;
            $tenant = $pitch->tenant;

            $unitPrice = $slot->manual_price;
            $totalPrice = $unitPrice;
            $remainingAmount = max(0, $totalPrice - $paidAmount);

            $bookingCode = 'MLB-M-' . strtoupper(Str::random(5));

            // 1. إنشاء الحجز اليدوي (بدون عمولة منصة على المانويل)
            $booking = Booking::create([
                'booking_code'      => $bookingCode,
                'tenant_id'         => $tenant->id,
                'pitch_id'          => $pitch->id,
                'pitch_slot_id'     => $slot->id,
                'user_id'           => null,
                'customer_name'     => $customerName,
                'customer_phone'    => $customerPhone,
                'booking_type'      => 'manual',
                'unit_price'        => $unitPrice,
                'total_price'       => $totalPrice,
                'deposit_amount'    => $paidAmount,
                'paid_amount'       => $paidAmount,
                'remaining_amount'  => $remainingAmount,
                'commission_amount' => 0.00,
                'status'            => 'confirmed',
            ]);

            // 2. إنشاء سجل الدفعة المالية المباشرة إن وُجد مدفوع
            if ($paidAmount > 0) {
                Payment::create([
                    'tenant_id'          => $tenant->id,
                    'booking_id'         => $booking->id,
                    'created_by_user_id' => $staffUser?->id,
                    'payment_code'       => 'PAY-' . strtoupper(Str::random(6)),
                    'amount'             => $paidAmount,
                    'currency'           => $tenant->currency ?? 'EGP',
                    'payment_method'     => $paymentMethod,
                    'payment_type'       => ($paidAmount >= $totalPrice) ? 'full' : 'deposit',
                    'status'             => 'completed',
                    'notes'              => 'دفعة كاش مباشرة بالملعب',
                    'paid_at'            => now(),
                ]);
            }

            // 3. تحديث حالة الفترة لـ booked
            $this->slotService->updateSlotStatus($slot, 'booked');

            return $booking;
        });
    }

    /**
     * إلغاء حجز قائم وفحص استحقاق الاسترداد
     */
    public function cancelBooking(Booking $booking, string $reason = 'طلب العميل الإلغاء', ?User $cancelledByUser = null): bool
    {
        if ($booking->status === 'cancelled') {
            return false;
        }

        return DB::transaction(function () use ($booking, $reason, $cancelledByUser) {
            $slot = $booking->slot;
            $tenant = $booking->tenant;

            // 1. فحص هل الإلغاء مسموح به قبل المهلة الشروطة (cancellation_deadline_hours)
            $bookingTime = Carbon::parse($slot->date->toDateString() . ' ' . $slot->start_time);
            $hoursUntilMatch = now()->diffInHours($bookingTime, false);

            $isEligibleForRefund = $hoursUntilMatch >= ($tenant->cancellation_deadline_hours ?? 6);

            // 2. إن كان مستحقاً للمبلغ وكان مدفوعاً سابقاً، يتم تسجيل عملية استرداد refund
            if ($isEligibleForRefund && $booking->paid_amount > 0) {
                Payment::create([
                    'tenant_id'          => $tenant->id,
                    'booking_id'         => $booking->id,
                    'created_by_user_id' => $cancelledByUser?->id,
                    'payment_code'       => 'REF-' . strtoupper(Str::random(6)),
                    'amount'             => $booking->paid_amount,
                    'currency'           => $tenant->currency ?? 'EGP',
                    'payment_method'     => 'online_gateway',
                    'payment_type'       => 'refund',
                    'status'             => 'completed',
                    'notes'              => 'استرداد مالي لإلغاء الحجز قبل المهلة الشروطة',
                    'paid_at'            => now(),
                ]);
            }

            // 3. تحديث حالة الحجز لـ cancelled
            $booking->update([
                'status'              => 'cancelled',
                'cancellation_reason' => $reason . ($isEligibleForRefund ? ' (مستحق للاسترداد)' : ' (غير مستحق للاسترداد - إلغاء متأخر)'),
                'cancelled_at'        => now(),
            ]);

            // 4. إعادة فتح الفترة الزمنية فوراً لتصبح متاحة للحجز مجدداً
            if ($slot) {
                $this->slotService->updateSlotStatus($slot, 'available');
            }

            return true;
        });
    }
}
