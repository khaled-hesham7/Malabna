<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

            // Schedule pattern (day of week: 0 for Sunday to 6 for Saturday)
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');

            // Active date duration
            $table->date('start_date');
            $table->date('end_date')->nullable();

            // Agreed price per match
            $table->decimal('agreed_price', 10, 2);

            // Schedule status
            $table->enum('status', ['active', 'paused', 'cancelled'])->default('active');

            $table->timestamps();

            // Indexes for automated scheduler queries
            $table->index(['tenant_id', 'pitch_id', 'status']);
            $table->index(['day_of_week', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_schedules');
    }
};
