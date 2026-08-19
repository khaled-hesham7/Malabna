<?php

namespace Database\Factories;

use App\Models\Pitch;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PitchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'pitch_id'           => Pitch::factory(),
            'date'               => now()->format('Y-m-d'),
            'start_time'         => '18:00:00',
            'end_time'           => '19:00:00',
            'price'              => 250.00,
            'status'             => 'available',
            'is_visible_online'  => true,
        ];
    }
}
