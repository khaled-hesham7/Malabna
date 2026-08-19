<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Pitch;
use App\Models\PitchSlot;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        return [
            'tenant_id'        => Tenant::factory(),
            'pitch_id'         => Pitch::factory(),
            'pitch_slot_id'    => PitchSlot::factory(),
            'user_id'          => User::factory(),
            'booking_code'     => 'MLB-' . strtoupper(Str::random(6)),
            'customer_name'    => fake()->name(),
            'customer_phone'   => fake()->phoneNumber(),
            'total_price'      => 200.00,
            'deposit_amount'   => 50.00,
            'paid_amount'      => 50.00,
            'remaining_amount' => 150.00,
            'commission_amount'=> 10.00,
            'status'           => 'confirmed',
            'booking_type'     => 'online',
        ];
    }
}
