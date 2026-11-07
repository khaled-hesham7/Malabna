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
        Schema::table('pitches', function (Blueprint $table) {
            $table->time('opening_time')->default('08:00:00')->after('night_start_at');
            $table->time('closing_time')->default('02:00:00')->after('opening_time');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(60)->after('closing_time'); // مدة الحجز بالدقائق (60 أو 90)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pitches', function (Blueprint $table) {
            $table->dropColumn(['opening_time', 'closing_time', 'slot_duration_minutes']);
        });
    }
};
