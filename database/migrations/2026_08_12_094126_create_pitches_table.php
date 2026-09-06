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

            // Sport category & specifications
            $table->string('sport_type', 50)->default('football');
            $table->string('court_size', 50)->nullable(); // 5_a_side, 7_a_side, etc.
            $table->string('surface_type', 50)->nullable();
            $table->boolean('is_indoor')->default(false);

            // 💰 Default Pricing Strategy (أسعار النهار والليل الافتراضية)
            $table->decimal('manual_day_price', 8, 2)->default(150.00);   // سعر المانويل (صبح)
            $table->decimal('manual_night_price', 8, 2)->default(250.00); // سعر المانويل (بليل)
            $table->decimal('online_day_price', 8, 2)->default(140.00);   // سعر الأونلاين (صبح)
            $table->decimal('online_night_price', 8, 2)->default(230.00); // سعر الأونلاين (بليل)

            // 🕒 Night Period Start Time (وقت بداية الفترة المسائية)
            $table->time('night_start_at')->default('16:00:00'); // الساعة 4 عصراً افتراضياً

            // Extra amenities as JSON array
            $table->json('amenities')->nullable();

            // Operational status
            $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
            $table->softDeletes();
            $table->timestamps();

            // Index for fast search and filtering
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
