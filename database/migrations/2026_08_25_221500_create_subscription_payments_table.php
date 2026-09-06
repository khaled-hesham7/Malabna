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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();

            // Link payment to tenant club
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Payment details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('EGP');
            $table->string('payment_method', 50)->default('cash');
            $table->string('transaction_id')->nullable()->unique();

            // Billing period
            $table->date('billing_period_start');
            $table->date('billing_period_end');

            // Payment status
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');

            // Admin who recorded / approved this payment
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Raw response or extra details
            $table->json('payload')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes for fast lookup
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
