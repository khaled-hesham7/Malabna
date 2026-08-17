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

            // Slot date and timing
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');

            // Calculated price for this specific slot
            $table->decimal('price', 10, 2);

            // Slot availability status
            $table->enum('status', [
                'available',
                'locked',
                'booked',
                'maintenance',
                'hidden'
            ])->default('available');

            // Hide slot from online mobile app/public web
            $table->boolean('is_visible_online')->default(true);

            // Concurrency lock fields (holds slot while user is paying)
            $table->unsignedBigInteger('locked_by_user_id')->nullable();
            $table->timestamp('locked_until')->nullable();

            $table->timestamps();

            // Unique constraint to prevent duplicate slots for same pitch and time
            $table->unique(['pitch_id', 'date', 'start_time'], 'pitch_slot_unique');

            // Indexes for fast calendar queries and online filtering
            $table->index(['pitch_id', 'date', 'status', 'is_visible_online'], 'slot_visibility_idx');
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
