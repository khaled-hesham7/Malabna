<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\Pitch;
use App\Services\PitchAvailabilityService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Exception;

class CreateBookingAction
{
    public function __construct(
        protected PitchAvailabilityService $availabilityService
    ) {}

    public function execute(array $data): Booking
    {
        $lockKey = "booking_lock_pitch_{$data['pitch_id']}_{$data['booking_date']}_{$data['start_time']}";

        return Cache::lock($lockKey, 5)->block(3, function () use ($data) {
            return DB::transaction(function () use ($data) {

                // 1. التحقق الشامل من التداخل قبل أي شيء
                $isAvailable = $this->availabilityService->isSlotAvailable(
                    $data['pitch_id'],
                    $data['booking_date'],
                    $data['start_time'],
                    $data['end_time']
                );

                if (!$isAvailable) {
                    throw new Exception("The selected slot overlaps with an existing booking, blocked time, or recurring schedule.", 422);
                }

                $pitch = Pitch::findOrFail($data['pitch_id']);

                // 2. الحسابات المالية
                $totalPrice = $pitch->calculatePrice($data['start_time'], $data['end_time']);

                $paidAmount = match ($data['payment_option']) {
                    'full'    => $totalPrice,
                    'deposit' => (float) $data['deposit_amount'],
                };

                if ($paidAmount > $totalPrice) {
                    throw new Exception("Paid amount cannot exceed total price ($totalPrice).", 422);
                }

                $remainingAmount = $totalPrice - $paidAmount;
                $isManual        = $data['payment_method'] === 'manual';

                if ($isManual) {
                    $bookingStatus = 'confirmed';
                    $paymentStatus = ($paidAmount == $totalPrice) ? 'paid' : 'partially_paid';
                } else {
                    $bookingStatus = 'pending';
                    $paymentStatus = 'unpaid';
                }

                // 3. الحفظ
                return Booking::create([
                    'pitch_id'         => $pitch->id,
                    'customer_name'    => $data['customer_name'],
                    'customer_phone'   => $data['customer_phone'],
                    'booking_date'     => $data['booking_date'],
                    'start_time'       => $data['start_time'],
                    'end_time'         => $data['end_time'],
                    'total_price'      => $totalPrice,
                    'paid_amount'      => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'payment_method'   => $data['payment_method'],
                    'payment_option'   => $data['payment_option'],
                    'payment_status'   => $paymentStatus,
                    'status'           => $bookingStatus,
                ]);
            });
        });
    }
}
