<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class PitchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'الملعب الرئيسي (' . fake()->numberBetween(1, 5) . ')',
            'sport_type' => fake()->randomElement(['football', 'padel', 'tennis']),
            'court_size' => fake()->randomElement(['5v5', '7v7', 'Standard']),
            'surface_type' => fake()->randomElement(['Artificial Grass', 'Acrylic', 'Clay']),
            'is_indoor' => fake()->boolean(),
            'description' => 'ملعب مجهز بأعلى مستويات الإضاءة والأرضيات المعتمدة.',
            'manual_day_price' => 150.00,
            'manual_night_price' => 250.00,
            'online_day_price' => 140.00,
            'online_night_price' => 230.00,
            'night_start_at' => '16:00:00',
            'amenities' => ['lighting', 'parking', 'shower'],
            'status' => $this->faker->randomElement(['active', 'maintenance', 'inactive']),
        ];
    }
}
