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
        Schema::create('pitches', function (Blueprint $table) {
            $table->id();

            // Link pitch to tenant venue
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Pitch basic info
            $table->string('name');
            $table->text('description')->nullable();

            // Sport category: football, padel, tennis, squash, basketball, volleyball, etc.
            $table->string('sport_type', 50)->default('football');

            // Court size/spec: 5_a_side, 7_a_side, single, double, full_court, etc.
            $table->string('court_size', 50)->nullable();

            // Surface material: artificial_grass, natural_grass, tartan, wood, acrylic, clay
            $table->string('surface_type', 50)->nullable();

            // Indoor or Outdoor court
            $table->boolean('is_indoor')->default(false);

            // Extra amenities as JSON array (e.g. ["lighting", "racket_rentals", "showers"])
            $table->json('amenities')->nullable();

            // Operational status
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->timestamps();

            // Index for fast search and filtering (تم تعديل is_active إلى status)
            $table->index(['tenant_id', 'sport_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pitches');
    }
};
