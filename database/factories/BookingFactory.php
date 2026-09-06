<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\Pitch;
use App\Models\PitchSlot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        $unitPrice = 250.00;
        $totalPrice = 250.00;
        $depositAmount = 50.00;
        $paidAmount = 250.00;
        $remainingAmount = 0.00;
        $commissionAmount = 25.00;

        return [
            'booking_code' => 'MLB-' . fake()->unique()->numberBetween(10000, 99999),
            'tenant_id' => Tenant::factory(),
            'pitch_id' => Pitch::factory(),
            'pitch_slot_id' => PitchSlot::factory(),
            'user_id' => User::factory(),
            'customer_name' => null,
            'customer_phone' => null,
            'booking_type' => 'online',
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'deposit_amount' => $depositAmount,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'commission_amount' => $commissionAmount,
            'status' => 'confirmed',
            'cancellation_reason' => null,
            'cancelled_at' => null,
            'recurring_schedule_id' => null,
        ];
    }
}
