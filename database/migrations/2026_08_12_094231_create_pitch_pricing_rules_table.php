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

            // Label for the rule (e.g. Morning, Peak Evening, Weekend)
            $table->string('name')->default('Standard');

            // Null means all days, 0 (Sunday) to 6 (Saturday)
            $table->unsignedTinyInteger('day_of_week')->nullable();

            // Time window for this price rule
            $table->time('start_time')->default('00:00:00');
            $table->time('end_time')->default('23:59:59');

            // Base hourly rate
            $table->decimal('price_per_hour', 10, 2);

            // Required deposit policy to confirm booking
            $table->enum('min_deposit_type', ['percentage', 'fixed', 'full'])->default('percentage');
            $table->decimal('min_deposit_amount', 10, 2)->default(50.00);

            // Rule activation toggle
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Composite index for fast pricing calculation
            $table->index(['pitch_id', 'day_of_week', 'is_active']);
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
