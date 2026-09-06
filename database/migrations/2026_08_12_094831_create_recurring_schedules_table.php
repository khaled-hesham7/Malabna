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
        Schema::create('recurring_schedules', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('pitch_id')->constrained('pitches')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Customer contact for offline recurring bookings
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();

            // 🎯 Recurring Type & Pricing Channel
            // manual: حجز ثابت من الكابتن في الملعب
            // online: حجز ثابت عبر التطبيق
            $table->enum('booking_type', ['online', 'manual'])->default('manual');

            // Schedule pattern (day of week: 0 for Sunday to 6 for Saturday)
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');

            // Active date duration
            $table->date('start_date');
            $table->date('end_date')->nullable(); // null تعني تثبيت مفتوح بدون تاريخ انتهاء

            // 💰 Agreed price per match (السعر المتفق عليه للماتش الثابت)
            $table->decimal('agreed_price', 10, 2);

            // Schedule status
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes for automated Cron Job queries
            $table->index(['tenant_id', 'pitch_id', 'status']);
            $table->index(['day_of_week', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_schedules');
    }
};
