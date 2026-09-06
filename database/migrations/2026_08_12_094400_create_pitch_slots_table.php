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
        Schema::create('pitch_slots', function (Blueprint $table) {
            $table->id();

            // Link slot to a pitch
            $table->foreignId('pitch_id')
                ->constrained('pitches')
                ->cascadeOnDelete();

            // Slot date and timing (تنسيق 24 ساعة)
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            // 💰 Dual Pricing (سعر المانويل وسعر الأونلاين المخصص للساعة دي)
            $table->decimal('manual_price', 8, 2);
            $table->decimal('online_price', 8, 2);

            // 🔒 Detailed Blocking & Availability Statuses
            // available: متاحة للجميع
            // booked: محجوزة بالفعل
            // blocked_all: مقفولة تماماً (صيانة/عطل)
            // blocked_online: مقفولة للأونلاين فقط ومتاحة للمباشر/اللوحة
            // blocked_manual: مقفولة لليدوي ومتاحة للأونلاين فقط
            $table->enum('status', [
                'available',
                'booked',
                'blocked_all',
                'blocked_online',
                'blocked_manual'
            ])->default('available');

            // 🔁 Recurring / Fixed Slot Flag (التثبيت الدوري)
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_customer_name')->nullable();
            // FK constraint added after recurring_schedules table is created
            // (see migration: add_recurring_schedule_foreign_keys)
            $table->unsignedBigInteger('recurring_schedule_id')->nullable();

            // ⏱️ Concurrency Lock Fields (حظر المؤقت أثناء غلق عملية الشراء أونلاين)
            $table->foreignId('locked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('locked_until')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Unique constraint to prevent duplicate slots for same pitch and time
            $table->unique(['pitch_id', 'date', 'start_time'], 'pitch_slot_unique');

            // Index for high-performance availability searches
            $table->index(['pitch_id', 'date', 'status'], 'slot_search_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pitch_slots');
    }
};
