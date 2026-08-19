<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Exception;

class SettleBookingPaymentAction
{
    /**
     * تسديد المبلغ المتبقي لحجز مدفوع جزئياً (عربون) في الملعب
     */
    public function execute(Booking $booking, float $amountPaid): Booking
    {
        return DB::transaction(function () use ($booking, $amountPaid) {

            // 1. التأكد من أن الحجز لم يتم سداده بالكامل مسبقاً
            if ($booking->payment_status === 'paid') {
                throw new Exception("This booking is already fully paid.", 422);
            }

            // 2. التأكد من أن المبلغ المدفوع لا يتجاوز القيمة المتبقية
            if ($amountPaid > $booking->remaining_amount) {
                throw new Exception("Paid amount exceeds remaining balance ({$booking->remaining_amount}).", 422);
            }

            // 3. حساب أرقام المبالغ الجديدة
            $newPaidAmount = $booking->paid_amount + $amountPaid;
            $newRemaining  = $booking->total_price - $newPaidAmount;

            // 4. تحديث حالة الدفع (مدفوع بالكامل أم ما زال جزء متبقي)
            $paymentStatus = ($newRemaining <= 0) ? 'paid' : 'partially_paid';

            // 5. تحديث بيانات الحجز وتأكيده فوراً بمجرد التحصيل الميداني
            $booking->update([
                'paid_amount'      => $newPaidAmount,
                'remaining_amount' => $newRemaining,
                'payment_status'   => $paymentStatus,
                'status'           => 'confirmed',
            ]);

            return $booking;
        });
    }
}

