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
        Schema::create('pitch_pricing_rules', function (Blueprint $table) {
            $table->id();

            // Link rule to a specific pitch
            $table->foreignId('pitch_id')
                ->constrained('pitches')
                ->cascadeOnDelete();

            // Label for the rule (e.g. Morning Rate, Weekend Peak, Holiday Rate)
            $table->string('name')->default('Standard');

            // Day of week condition: Null means applies to all days, 0 (Sunday) to 6 (Saturday)
            $table->unsignedTinyInteger('day_of_week')->nullable();

            // Time window for this price rule (e.g. 18:00:00 to 00:00:00)
            $table->time('start_time')->default('00:00:00');
            $table->time('end_time')->default('23:59:59');

            // 💰 Dual Pricing Strategy for Rules
            $table->decimal('manual_price_per_hour', 8, 2); // سعر المانويل للقاعدة دي
            $table->decimal('online_price_per_hour', 8, 2); // سعر الأونلاين للقاعدة دي

            // Required deposit policy to confirm booking for this time rule
            $table->enum('min_deposit_type', ['percentage', 'fixed', 'full'])->default('percentage');
            $table->decimal('min_deposit_amount', 8, 2)->default(50.00);

            // Rule activation toggle (active / inactive)
            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->softDeletes();
            $table->timestamps();

            // Composite index for fast pricing lookup queries
            $table->index(['pitch_id', 'day_of_week', 'status'], 'pricing_rules_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pitch_pricing_rules');
    }
};
