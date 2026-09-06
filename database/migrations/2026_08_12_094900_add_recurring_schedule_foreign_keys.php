<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * هذه الميجريشن تضيف الـ Foreign Key constraints لعمود recurring_schedule_id
 * في جدولي pitch_slots و bookings، وذلك بعد إنشاء جدول recurring_schedules.
 *
 * السبب: لا يمكن إضافة FK constraint على جدول لم يُنشأ بعد، لذا تم فصل هذه
 * العلاقة الدائرية في ميجريشن مستقلة تُنفَّذ بعد إنشاء جميع الجداول المرتبطة.
 */
return new class extends Migration
{
    public function up(): void
    {
        // إضافة FK constraint لـ pitch_slots.recurring_schedule_id
        Schema::table('pitch_slots', function (Blueprint $table) {
            $table->foreign('recurring_schedule_id')
                ->references('id')
                ->on('recurring_schedules')
                ->nullOnDelete();
        });

        // إضافة FK constraint لـ bookings.recurring_schedule_id
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('recurring_schedule_id')
                ->references('id')
                ->on('recurring_schedules')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['recurring_schedule_id']);
        });

        Schema::table('pitch_slots', function (Blueprint $table) {
            $table->dropForeign(['recurring_schedule_id']);
        });
    }
};
