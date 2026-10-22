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
        Schema::table('users', function (Blueprint $table) {
            // Link staff or owner to a venue (null for super admin and customers)
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->nullOnDelete();

            // Make email optional if user registers with phone only
            $table->string('email')->nullable()->change();

            // Account status
            $table->enum('status', ['active', 'inactive', 'banned'])
                ->default('active')
                ->after('password');

            $table->softDeletes();
        });

        // 🔒 إضافة Foreign Key لـ owner_id في جدول tenants بعد ما Users بقى جاهز
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // فك ربط Foreign Key من جدول tenants أولاً
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
        });

        // إزالة الحقول والعلاقات من جدول users
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'tenant_id',
                'status'
            ]);
        });
    }
};
