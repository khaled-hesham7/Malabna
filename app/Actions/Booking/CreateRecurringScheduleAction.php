<?php

namespace App\Actions\Booking;

use App\Models\Pitch;
use App\Models\RecurringSchedule;
use Exception;

class CreateRecurringScheduleAction
{
    /**
     * Create a weekly recurring slot reservation.
     */
    public function execute(array $data): RecurringSchedule
    {
        $pitch = Pitch::findOrFail($data['pitch_id']);

        // 1. التحقق من عدم وجود تثبيت أسبوعي آخر لنفس اليوم والوقت
        $hasRecurringConflict = RecurringSchedule::where('pitch_id', $pitch->id)
            ->where('day_of_week', $data['day_of_week'])
            ->where('status', 'active')
            ->where(function ($query) use ($data) {
                $query->where('start_time', '<', $data['end_time'])
                    ->where('end_time', '>', $data['start_time']);
            })
            ->exists();

        if ($hasRecurringConflict) {
            throw new Exception('A recurring reservation already exists for this day and time.', 422);
        }

        // 2. إنشاء التثبيت الأسبوعي
        return RecurringSchedule::create([
            'tenant_id'      => $pitch->tenant_id,
            'pitch_id'       => $pitch->id,
            'day_of_week'    => $data['day_of_week'],
            'start_time'     => $data['start_time'],
            'end_time'       => $data['end_time'],
            'customer_name'  => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'] ?? null,
            'status'         => 'active',
        ]);
    }
}
