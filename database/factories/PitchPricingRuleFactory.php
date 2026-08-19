<?php

namespace Database\Factories;

use App\Models\Pitch;
use Illuminate\Database\Eloquent\Factories\Factory;

class PitchPricingRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pitch_id' => Pitch::factory(),
            'name' => 'التسعير القياسي',
            'day_of_week' => null, // شغالة طول الأسبوع
            'start_time' => '00:00:00',
            'end_time' => '23:59:59',
            'price_per_hour' => fake()->randomElement([200.00, 250.00, 300.00]),
            'min_deposit_type' => 'percentage',
            'min_deposit_amount' => 25.00, // 25% عربون
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
