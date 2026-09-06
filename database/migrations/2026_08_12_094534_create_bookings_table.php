<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Unique readable booking reference (e.g. MLB-93821)
            $table->string('booking_code', 30)->unique();

            // Relations
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('pitch_id')->constrained('pitches')->cascadeOnDelete();
            $table->foreignId('pitch_slot_id')->constrained('pitch_slots')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // FK constraint added after recurring_schedules table is created
            // (see migration: add_recurring_schedule_foreign_keys)
            $table->unsignedBigInteger('recurring_schedule_id')->nullable();

            // Customer details for walk-in / manual bookings
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();

            // 🎯 Booking Source (مصدر الحجز)
            // online: حجز من التطبيق (بناءً على online_price)
            // manual: حجز مباشر من اللوحة/الكابتن (بناءً على manual_price)
            $table->enum('booking_type', ['online', 'manual'])->default('online');

            // 💰 Financial Snapshot (حفظ السعر اللحظي لتجنب تغيره مستقبلاً)
            $table->decimal('unit_price', 8, 2); // السعر الأصلي للساعة وقت الحجز
            $table->decimal('total_price', 10, 2); // الإجمالي بعد الخصومات أو الإضافات
            $table->decimal('deposit_amount', 10, 2)->default(0.00); // العربون
            $table->decimal('paid_amount', 10, 2)->default(0.00); // المدفوع فعلياً
            $table->decimal('remaining_amount', 10, 2)->default(0.00); // المتبقي
            $table->decimal('commission_amount', 10, 2)->default(0.00); // عمولة السيستم

            // Booking state
            $table->enum('status', [
                'pending_payment',
                'confirmed',
                'completed',
                'cancelled',
                'no_show'
            ])->default('pending_payment');

            // Cancellation details
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes for fast lookup & analytics dashboards
            $table->index(['tenant_id', 'status', 'booking_type']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
