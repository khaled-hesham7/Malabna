<?php

namespace Database\Factories;

use App\Models\Pitch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PitchSlotFactory extends Factory
{
    public function definition(): array
    {
        // استخدام Carbon لضمان تنسيق الوقت الصحيح (مثال: 16:00:00 إلى 17:00:00)
        $startHour = fake()->numberBetween(16, 22);
        $startTime = Carbon::createFromTime($startHour, 0, 0);
        $endTime = (clone $startTime)->addHour();

        return [
            'pitch_id'          => Pitch::factory(),
            'date'              => now()->addDays(fake()->numberBetween(0, 7))->format('Y-m-d'),
            'start_time'        => $startTime->format('H:i:s'),
            'end_time'          => $endTime->format('H:i:s'),
            'price'             => 250.00,
            'status'            => 'available', // available, locked, booked, unavailable
            'is_visible_online' => true,
            'locked_by_user_id' => null,
            'locked_until'      => null,
        ];
    }

    // States مفيدة للاختبارات والـ Seeders
    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'            => 'locked',
            'locked_by_user_id' => User::factory(),
            'locked_until'      => now()->addMinutes(10),
        ]);
    }

    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'booked',
        ]);
    }
}
