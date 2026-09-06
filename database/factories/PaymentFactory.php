<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'booking_id' => Booking::factory(),
            'created_by_user_id' => User::factory(),
            'payment_code' => 'PAY-' . fake()->unique()->numberBetween(100000, 999999),
            'amount' => 250.00,
            'currency' => 'EGP',
            'payment_method' => fake()->randomElement(['cash', 'vodafone_cash', 'instapay', 'online_gateway']),
            'payment_type' => 'full',
            'status' => 'completed',
            'transaction_id' => 'TXN-' . fake()->numberBetween(10000, 99999),
            'notes' => 'Test payment transaction',
            'paid_at' => now(),
        ];
    }
}
