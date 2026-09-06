<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'amount' => 1500.00,
            'currency' => 'EGP',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'SUB-TXN-' . fake()->numberBetween(10000, 99999),
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->addMonth()->startOfMonth()->toDateString(),
            'status' => 'completed',
            'created_by_user_id' => User::factory(),
            'notes' => 'Monthly tenant subscription fee payment',
            'paid_at' => now(),
        ];
    }
}
