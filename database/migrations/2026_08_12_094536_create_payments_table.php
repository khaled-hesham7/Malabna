<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Link payment to booking
            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            // Payment provider: paymob, fawry, instapay, cash, etc.
            $table->string('payment_method', 50)->default('cash');

            // Gateway external reference ID
            $table->string('transaction_id')->nullable()->unique();

            // Payment amount and currency
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('EGP');

            // Status (استبدال successful بـ completed للتوافق)
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');

            // Gateway full response payload
            $table->json('payload')->nullable();

            $table->timestamps();

            // Index
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
