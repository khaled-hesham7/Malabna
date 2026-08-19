<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company() . ' Sports Club';
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'company_name' => $name,
            'city' => $this->faker->city(), // <-- أضف هذا السطر هنا
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(29.9, 30.1),
            'longitude' => fake()->longitude(31.1, 31.4),
            'subscription_price' => fake()->randomElement([1000.00, 1500.00, 2000.00]),
            'subscription_expires_at' => now()->addYear(),
            'commission_type' => 'percentage',
            'commission_rate' => 10.00,
            // 'status'       => $this->faker->randomElement(['active', 'maintenance', 'inactive']),
            'status' => 'active'
        ];
    }
}
