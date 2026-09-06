<?php

namespace Database\Factories;

use App\Models\Pitch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecurringScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'pitch_id' => Pitch::factory(),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'agreed_price' => 200.00,
            'day_of_week' => 5, // Friday
            'start_time' => '20:00:00',
            'end_time' => '21:00:00',
            'booking_type' => 'manual',
            'status' => 'active',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'notes' => 'Weekly Friday match schedule',
        ];
    }
}
