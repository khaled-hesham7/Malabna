<?php

namespace App\Actions\Booking;

use App\Models\Booking;
use App\Models\Pitch;
use Exception;

class BlockPitchSlotAction
{
    /**
     * Block or hide a specific pitch slot for maintenance or manager request.
     */
    public function execute(array $data): Booking
    {
        $pitch = Pitch::findOrFail($data['pitch_id']);

        // 1. التأكد من عدم وجود حجز قائم في نفس الفترة
        $hasConflict = Booking::where('pitch_id', $pitch->id)
            ->whereDate('booking_date', $data['block_date'])
            ->whereIn('status', ['confirmed', 'pending'])
            ->where(function ($query) use ($data) {
                $query->where('start_time', '<', $data['end_time'])
                    ->where('end_time', '>', $data['start_time']);
            })
            ->exists();

        if ($hasConflict) {
            throw new Exception('Cannot block this slot because an active booking already exists.', 422);
        }

        // 2. إنشاء حجز إداري بحالة blocked
        return Booking::create([
            'tenant_id'      => $pitch->tenant_id,
            'pitch_id'       => $pitch->id,
            'booking_date'   => $data['block_date'],
            'start_time'     => $data['start_time'],
            'end_time'       => $data['end_time'],
            'customer_name'  => 'Owner Block',
            'customer_phone' => 'N/A',
            'total_price'    => 0,
            'payment_method' => 'cash',
            'status'         => 'blocked', // حالة خاصة بحظر الساعات
            'notes'          => $data['reason'] ?? 'Blocked by owner',
        ]);
    }
}
