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
        Route::delete('{pitch}', [\App\Http\Controllers\Api\Tenant\PartnerPitchController::class, 'destroy']);
    });

    // =====================================
    // 3. Super Admin Approval Routes
    // =====================================
    Route::prefix('admin')->middleware(['auth:sanctum', 'role:super_admin'])->group(function () {
        Route::get('tenants', [AdminTenantApprovalController::class, 'index']);
        Route::get('tenants/{tenant}', [AdminTenantApprovalController::class, 'show']);
        Route::post('tenants/{tenant}/approve', [AdminTenantApprovalController::class, 'approve']);
        Route::post('tenants/{tenant}/reject', [AdminTenantApprovalController::class, 'reject']);
    });
});
