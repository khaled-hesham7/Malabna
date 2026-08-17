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

            // Main contact phone number for booking and login
            $table->string('phone', 20)->unique()->after('name');

            // Make email optional if user registers with phone only
            $table->string('email')->nullable()->change();

            // High-level user account type
            $table->enum('user_type', [
                'super_admin',
                'tenant_owner',
                'tenant_staff',
                'customer'
            ])->default('customer')->after('password');

            // Account status
            $table->enum('status', ['active', 'inactive', 'banned'])
                ->default('active')
                ->after('user_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn([
                'tenant_id',
                'phone',
                'user_type',
                'status'
            ]);
        });
    }
};
