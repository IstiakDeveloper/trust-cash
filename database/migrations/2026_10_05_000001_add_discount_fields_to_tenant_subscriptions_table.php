<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * CENTRAL migration: add discount fields to tenant_subscriptions and tenants
     */
    public function up(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->string('discount_type')->default('none')->after('billing_cycle'); // none, percentage, fixed
            $table->decimal('discount_value', 10, 2)->default(0.00)->after('discount_type');
            $table->string('discount_note')->nullable()->after('discount_value');
            $table->decimal('custom_price', 10, 2)->nullable()->after('discount_note');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('discount_type')->default('none')->after('status');
            $table->decimal('discount_value', 10, 2)->default(0.00)->after('discount_type');
            $table->string('discount_note')->nullable()->after('discount_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_note', 'custom_price']);
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['discount_type', 'discount_value', 'discount_note']);
        });
    }
};
