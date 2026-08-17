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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            // Venue owner relation
            $table->unsignedBigInteger('owner_id')->nullable();

            // Basic venue info
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Contact and location details
            $table->string('phone', 20);
            $table->string('city', 100);
            $table->text('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Approval status by super admin
            $table->enum('status', ['pending', 'active', 'suspended', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();

            // Custom subscription set by super admin
            $table->decimal('subscription_price', 10, 2)->default(0.00);
            $table->enum('subscription_billing_period', ['monthly', 'yearly', 'custom'])->default('monthly');
            $table->timestamp('subscription_starts_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();

            // Custom commission set by super admin
            $table->enum('commission_type', ['none', 'percentage', 'fixed'])->default('none');
            $table->decimal('commission_rate', 8, 2)->default(0.00);

            // Booking settings
            $table->unsignedInteger('cancellation_deadline_hours')->default(6);

            $table->timestamps();

            // Indexes for fast search
            $table->index(['status', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
