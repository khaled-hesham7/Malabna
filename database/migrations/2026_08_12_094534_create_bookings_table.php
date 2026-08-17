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
            $table->foreignId('pitch_slot_id')->constrained('pitch_slots')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Customer details for walk-in / manual bookings
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();

            // Financials
            $table->decimal('total_price', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0.00);
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('remaining_amount', 10, 2)->default(0.00);
            $table->decimal('commission_amount', 10, 2)->default(0.00);

            // Booking state
            $table->enum('status', [
                'pending_payment',
                'confirmed',
                'completed',
                'cancelled',
                'no_show'
            ])->default('pending_payment');

            // Booking source
            $table->enum('booking_type', ['online', 'manual'])->default('online');

            // Cancellation notes if any
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'status']);
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
