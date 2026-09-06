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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Direct link to tenant for fast multi-tenant financial reporting
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Link payment to booking
            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->restrictOnDelete();

            // Staff or Customer who recorded/processed this payment
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('payment_code')->nullable()->unique();

            // Payment classification: deposit (عربون), full (كامل), remaining (باقي الحجز)
            $table->enum('payment_type', ['deposit', 'full', 'remaining', 'refund'])->default('full');

            // Payment method/provider: paymob, fawry, instapay, vodafone_cash, cash
            $table->string('payment_method', 50)->default('cash');

            // Gateway external reference ID
            $table->string('transaction_id')->nullable()->unique();

            // Payment amount and currency
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('EGP');

            // Payment status
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');

            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Gateway full response payload for audit & webhooks
            $table->json('payload')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes for accounting & reports performance
            $table->index(['tenant_id', 'status', 'payment_method']);
            $table->index(['booking_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
