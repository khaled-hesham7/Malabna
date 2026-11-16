<?php

use App\Http\Controllers\Api\Admin\AdminTenantApprovalController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\TenantRegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Malabna Web Platform (v1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // =====================================
    // 1. Unified Authentication Routes
    // =====================================
    Route::prefix('auth')->group(function () {
        // تسجيل الدخول الموحد للجميع (سوبر أدمن، صاحب منشأة، موظف، لاعب)
        Route::post('login', [LoginController::class, 'login']);

        // تسجيل لاعب جديد
        Route::post('register', [RegisterController::class, 'register']);

        // تقديم طلب تسجيل منشأة جديدة (نادي/ملاعب)
        Route::post('tenant/register', [TenantRegisterController::class, 'register']);

        // المسارات المحمية بالتوكن (الملف الشخصي وتسجيل الخروج)
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [LoginController::class, 'me']);
            Route::post('logout', [LoginController::class, 'logout']);
        });
    });

    // =====================================
    // 2. Tenant Subscription & Billing Routes
    // =====================================
    Route::prefix('partner/subscription')->middleware(['auth:sanctum', 'role:tenant_owner'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Tenant\TenantSubscriptionController::class, 'show']);
        Route::post('pay', [\App\Http\Controllers\Api\Tenant\TenantSubscriptionController::class, 'pay']);
    });

    // =====================================
    // 3. Partner Pitch Management Routes (CRUD)
    // =====================================
    Route::prefix('partner/pitches')->middleware(['auth:sanctum', 'role:tenant_owner|tenant_staff'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Tenant\PartnerPitchController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Tenant\PartnerPitchController::class, 'store']);
        Route::get('{pitch}', [\App\Http\Controllers\Api\Tenant\PartnerPitchController::class, 'show']);
        Route::post('{pitch}', [\App\Http\Controllers\Api\Tenant\PartnerPitchController::class, 'update']); // POST to support multipart/form-data with media files
        Route::patch('{pitch}/toggle-status', [\App\Http\Controllers\Api\Tenant\PartnerPitchController::class, 'toggleStatus']);
        Route::delete('{pitch}', [\App\Http\Controllers\Api\Tenant\PartnerPitchController::class, 'destroy']);

        // جدول الساعات والتحكم في اليوم
        Route::get('{pitch}/slots', [\App\Http\Controllers\Api\Tenant\PartnerSlotController::class, 'index']);
        Route::post('{pitch}/slots/toggle-day', [\App\Http\Controllers\Api\Tenant\PartnerSlotController::class, 'toggleBlockDay']);
    });

    // =====================================
    // 4. Partner Slot Actions (Single Slot Block & Manual Booking)
    // =====================================
    Route::prefix('partner/slots')->middleware(['auth:sanctum', 'role:tenant_owner|tenant_staff'])->group(function () {
        Route::patch('{slot}/toggle-block', [\App\Http\Controllers\Api\Tenant\PartnerSlotController::class, 'toggleBlockSlot']);
        Route::patch('{slot}/update-price', [\App\Http\Controllers\Api\Tenant\PartnerSlotController::class, 'updatePrice']);
        Route::post('{slot}/manual-book', [\App\Http\Controllers\Api\Tenant\PartnerSlotController::class, 'manualBook']);
        Route::post('{slot}/cancel-booking', [\App\Http\Controllers\Api\Tenant\PartnerSlotController::class, 'cancelBooking']);
    });

    // =====================================
    // 5. Partner Recurring Schedules (الحجوزات الأسبوعية الثابتة)
    // =====================================

    Route::prefix('partner/recurring-schedules')->middleware(['auth:sanctum', 'role:tenant_owner|tenant_staff'])->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Tenant\PartnerRecurringScheduleController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Tenant\PartnerRecurringScheduleController::class, 'store']);
        Route::get('{recurringSchedule}', [\App\Http\Controllers\Api\Tenant\PartnerRecurringScheduleController::class, 'show']);
        Route::match(['put', 'patch'], '{recurringSchedule}', [\App\Http\Controllers\Api\Tenant\PartnerRecurringScheduleController::class, 'update']);
        Route::patch('{recurringSchedule}/toggle-pause', [\App\Http\Controllers\Api\Tenant\PartnerRecurringScheduleController::class, 'togglePause']);
        Route::delete('{recurringSchedule}', [\App\Http\Controllers\Api\Tenant\PartnerRecurringScheduleController::class, 'destroy']);
        Route::post('{recurringSchedule}/release-date', [\App\Http\Controllers\Api\Tenant\PartnerRecurringScheduleController::class, 'releaseDate']);
    });

    // =====================================
    // 6. Partner Staff Management (إدارة موظفي المنشأة)
    // =====================================
    Route::prefix('partner/staff')->middleware(['auth:sanctum', 'role:tenant_owner'])->group(function () {
        Route::get('permissions-list', [\App\Http\Controllers\Api\Tenant\PartnerStaffController::class, 'permissionsList']);
        Route::get('/', [\App\Http\Controllers\Api\Tenant\PartnerStaffController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Tenant\PartnerStaffController::class, 'store']);
        Route::get('{staff}', [\App\Http\Controllers\Api\Tenant\PartnerStaffController::class, 'show']);
        Route::match(['put', 'patch'], '{staff}', [\App\Http\Controllers\Api\Tenant\PartnerStaffController::class, 'update']);
        Route::patch('{staff}/toggle-status', [\App\Http\Controllers\Api\Tenant\PartnerStaffController::class, 'toggleStatus']);
        Route::delete('{staff}', [\App\Http\Controllers\Api\Tenant\PartnerStaffController::class, 'destroy']);
    });

    // =====================================
    // 7. Profile Management (الملف الشخصي للمستخدم الحالي)
    // =====================================
    Route::prefix('profile')->middleware('auth:sanctum')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'show']);
        Route::match(['put', 'patch'], '/', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'update']);
        Route::put('change-password', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'changePassword']);
        Route::post('avatar', [\App\Http\Controllers\Api\Profile\ProfileController::class, 'uploadAvatar']);
    });

    // =====================================
    // 8. Super Admin Approval Routes
    // =====================================
    Route::prefix('admin')->middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
        Route::get('tenants', [AdminTenantApprovalController::class, 'index']);
        Route::get('tenants/{tenant}', [AdminTenantApprovalController::class, 'show']);
        Route::post('tenants/{tenant}/approve', [AdminTenantApprovalController::class, 'approve']);
        Route::post('tenants/{tenant}/reject', [AdminTenantApprovalController::class, 'reject']);
    });
});
