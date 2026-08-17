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
            'description' => 'ملعب مجهز بأعلى مستويات الإضاءة والأرضيات المعتمدة.',
'status'       => $this->faker->randomElement(['active', 'maintenance', 'inactive']),        ];
    }
}
