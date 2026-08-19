<?php

use App\Models\Booking;
use App\Models\Pitch;
use App\Models\PitchSlot;
use App\Models\RecurringSchedule;
use App\Services\PitchAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @property PitchAvailabilityService $service
 * @property Pitch $pitch
 * @mixin \Tests\TestCase
 */
uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PitchAvailabilityService::class);
    $this->pitch = Pitch::factory()->create();
});

it('returns true when time slot is completely available', function () {
    $isAvailable = $this->service->isSlotAvailable(
        $this->pitch->id,
        '2026-09-01',
        '18:00:00',
        '19:00:00'
    );

    expect($isAvailable)->toBeTrue();
});

it('returns false when there is a conflicting booking', function () {
    // إنشاء Slot مرتبط بالحجز
    $slot = PitchSlot::factory()->create([
        'pitch_id'   => $this->pitch->id,
        'date'       => '2026-09-01',
        'start_time' => '18:00:00',
        'end_time'   => '19:00:00',
    ]);

    Booking::factory()->create([
        'tenant_id'     => $this->pitch->tenant_id,
        'pitch_id'      => $this->pitch->id,
        'pitch_slot_id' => $slot->id,
        'status'        => 'confirmed',
    ]);

    $isAvailable = $this->service->isSlotAvailable(
        $this->pitch->id,
        '2026-09-01',
        '18:30:00',
        '19:30:00'
    );

    expect($isAvailable)->toBeFalse();
});

it('ignores specified booking id when checking availability', function () {
    $slot = PitchSlot::factory()->create([
        'pitch_id'   => $this->pitch->id,
        'date'       => '2026-09-01',
        'start_time' => '18:00:00',
        'end_time'   => '19:00:00',
    ]);

    $booking = Booking::factory()->create([
        'tenant_id'     => $this->pitch->tenant_id,
        'pitch_id'      => $this->pitch->id,
        'pitch_slot_id' => $slot->id,
        'status'        => 'confirmed',
    ]);

    $isAvailable = $this->service->isSlotAvailable(
        $this->pitch->id,
        '2026-09-01',
        '18:00:00',
        '19:00:00',
        $booking->id
    );

    expect($isAvailable)->toBeTrue();
});

it('returns false when slot overlaps with a blocked pitch slot', function () {
    // استخدام حقل date الصحيح بدلاً من blocked_date
    PitchSlot::factory()->create([
        'pitch_id'   => $this->pitch->id,
        'date'       => '2026-09-01',
        'start_time' => '18:00:00',
        'end_time'   => '19:00:00',
        'status'     => 'blocked', // أو الحالة الخاصة بالحظر حسب الـ Business Logic
    ]);

    $isAvailable = $this->service->isSlotAvailable(
        $this->pitch->id,
        '2026-09-01',
        '18:00:00',
        '19:00:00'
    );

    expect($isAvailable)->toBeFalse();
});

it('returns false when slot overlaps with an active recurring schedule', function () {
    // 2026-09-01 يعتبر يوم الثلاثاء (day_of_week = 2 في ISO أو حسب التنسيق المتبع عندك)
    RecurringSchedule::factory()->create([
        'pitch_id'    => $this->pitch->id,
        'day_of_week' => 2, // أو 'Tuesday' حسب العمود في الجدول
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
        'status'      => 'active',
    ]);

    $isAvailable = $this->service->isSlotAvailable(
        $this->pitch->id,
        '2026-09-01',
        '18:00:00',
        '19:00:00'
    );

    expect($isAvailable)->toBeFalse();
});
