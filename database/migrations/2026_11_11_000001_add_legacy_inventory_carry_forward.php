<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_policies', function (Blueprint $table) {
            $table->timestamp('legacy_cutover_at')->nullable();
        });
        Schema::table('product_stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('legacy_sale_item_id')->nullable()->index();
        });
        Schema::create('legacy_sale_openings', function (Blueprint $table) {
            $table->unsignedBigInteger('sale_item_id')->primary();
            $table->decimal('quantity', 20, 6);
            $table->decimal('cost', 24, 6);
            $table->boolean('return_history_ambiguous')->default(false);
        });
        Schema::create('legacy_sale_return_links', function (Blueprint $table) {
            $table->unsignedBigInteger('return_item_id')->primary();
            $table->unsignedBigInteger('sale_item_id')->index();
        });
        Schema::create('legacy_inventory_openings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_key')->default(0);
            $table->unsignedBigInteger('opening_stock_id')->nullable();
            $table->decimal('quantity', 20, 6);
            $table->decimal('value', 24, 6);
            $table->decimal('unit_cost', 24, 8);
            $table->json('source_stock_ids');
            $table->timestamp('created_at');
            $table->unique(['product_id', 'variant_key']);
        });
        Schema::create('legacy_inventory_values', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_id')->primary();
            $table->decimal('reporting_cost', 24, 6);
        });
        Schema::create('legacy_balance_carry_forwards', function (Blueprint $table) {
            $table->id();
            $table->string('account')->unique();
            $table->decimal('stored_balance', 24, 6);
            $table->decimal('document_balance', 24, 6);
            $table->decimal('difference', 24, 6);
            $table->date('date');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        // Carry-forward evidence and posted lots must survive a code rollback.
        // Restoring a database backup is required to reverse an applied cutover.
    }
};
