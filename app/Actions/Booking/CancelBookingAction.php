<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class CancelBookingAction
{
    /**
     * Cancel an existing booking with cancellation policy checks.
     */
    public function execute(Booking $booking, array $data = []): Booking
    {
        return DB::transaction(function () use ($booking, $data) {
            // 1. إعادة قفل الحجز للتعديل (Pessimistic Locking)
            $booking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. التحقق من حالة الحجز
            if (in_array($booking->status, ['cancelled', 'completed', 'blocked'])) {
                throw new Exception("Cannot cancel a {$booking->status} booking.", 422);
            }

            // 3. التحقق من أن موعد الحجز لم يمر بعد
            $bookingStartDateTime = Carbon::parse("{$booking->booking_date} {$booking->start_time}");
            if (now()->greaterThanOrEqualTo($bookingStartDateTime)) {
                throw new Exception('Cannot cancel a past or ongoing booking.', 422);
            }

            // 4. تطبيق سياسة الإلغاء (يمنع الإلغاء إذا متبقي أقل من 6 ساعات)
            $hoursDifference = now()->diffInHours($bookingStartDateTime, false);
            if ($hoursDifference < 6) {
                throw new Exception('Cancellation is not allowed less than 6 hours before start time.', 422);
            }

            // 5. تسجيل سبب الإلغاء وتحديث حالة الحجز
            $reason = isset($data['reason']) ? trim($data['reason']) : 'No reason provided';
            $existingNotes = $booking->notes ? trim($booking->notes) . ' | ' : '';

            $booking->update([
                'status' => 'cancelled',
                'notes'  => $existingNotes . "Cancelled: {$reason}",
            ]);

            return $booking;
        });
    }
}
