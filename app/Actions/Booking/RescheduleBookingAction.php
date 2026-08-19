<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Services\PitchAvailabilityService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class RescheduleBookingAction
{
    public function __construct(
        protected PitchAvailabilityService $availabilityService
    ) {}

    /**
     * Reschedule an existing booking to a new date and time.
     */
    public function execute(Booking $booking, array $data): Booking
    {
        return DB::transaction(function () use ($booking, $data) {
            // 1. إعادة قفل الحجز للتعديل (Pessimistic Locking)
            $booking = Booking::where('id', $booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. التحقق من حالة الحجز
            if (in_array($booking->status, ['cancelled', 'completed', 'blocked'])) {
                throw new Exception("Cannot reschedule a {$booking->status} booking.", 422);
            }

            // 3. التحقق من أن الموعد القديم لم يمر بعد
            $oldStart = Carbon::parse("{$booking->booking_date} {$booking->start_time}");
            if (now()->greaterThanOrEqualTo($oldStart)) {
                throw new Exception('Cannot reschedule a past or ongoing booking.', 422);
            }

            // 4. الفحص الشامل للوفرة باستخدام الخدمة
            $isAvailable = $this->availabilityService->isSlotAvailable(
                pitchId: $booking->pitch_id,
                date: $data['new_date'],
                startTime: $data['start_time'],
                endTime: $data['end_time'],
                ignoreBookingId: $booking->id // 👈 استثناء الحجز الحالي من المقارنة
            );

            if (! $isAvailable) {
                throw new Exception('The requested new time slot is not available.', 422);
            }

            // 5. تحديث موعد الحجز
            $booking->update([
                'booking_date' => $data['new_date'],
                'start_time'   => $data['start_time'],
                'end_time'     => $data['end_time'],
            ]);

            return $booking;
        });
    }
}
