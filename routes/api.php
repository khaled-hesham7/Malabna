<?php

use App\Http\Controllers\Api\BlockPitchSlotController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CancelBookingController;
use App\Http\Controllers\Api\PitchSlotController;
use App\Http\Controllers\Api\RecurringScheduleController;
use App\Http\Controllers\Api\RescheduleBookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 1. بيانات المستخدم المسجل
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 2. السلوتات والملاعب (Public / Core)
Route::get('/pitches/{pitch}/slots', PitchSlotController::class);

// 3. الحجوزات (Bookings Domain)
Route::prefix('bookings')->group(function () {
    Route::post('/', BookingController::class);                              // إنشاء حجز جديد
    Route::patch('/{booking}/cancel', CancelBookingController::class);      // إلغاء حجز محدد
    Route::patch('/{booking}/reschedule', RescheduleBookingController::class); // إعادة جدولة حجز محدد
});

// 4. إدارة المواعيد والأوقات المحظورة (Owner / Admin)
Route::post('/pitches/{pitch}/block-slot', BlockPitchSlotController::class);
Route::post('/recurring-schedules', RecurringScheduleController::class);
